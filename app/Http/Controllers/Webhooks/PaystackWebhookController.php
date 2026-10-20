<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();
        
        $data = json_decode($payload, true) ?: [];
        $body = $data['data'] ?? [];
        $reference = $body['reference'] ?? null;

        $secret = config('services.paystack.secret_key', env('PAYSTACK_SECRET_KEY'));

        if ($reference) {
            $payment = Payment::where('gateway_reference', $reference)->with('invoice')->first();
            if ($payment && $payment->invoice) {
                $service = \App\Services\Payment\PaymentGatewayFactory::resolveForInvoice($payment->invoice, 'paystack');
                if ($service->getSecretKey()) {
                    $secret = $service->getSecretKey();
                }
            }
        }

        if (!$this->verifySignature($payload, $signature, $secret)) {
            Log::warning('Paystack Webhook Invalid Signature', ['reference' => $reference]);
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $event = $data['event'] ?? null;

        if ($event === 'charge.success') {
            if ($reference) {
                $this->processSuccessfulPayment($reference, $body);
            }
        }

        return response()->json(['message' => 'Webhook received']);
    }

    protected function verifySignature($payload, $signature, $secret)
    {
        if (!$signature || !$secret) return false;
        
        $computedSignature = hash_hmac('sha512', $payload, $secret);
        return hash_equals($computedSignature, $signature);
    }

    protected function processSuccessfulPayment($reference, $data)
    {
        // Extract payment method channel
        $data['channel'] = $data['channel'] ?? 'paystack';
        
        app(\App\Services\Payment\PaymentHandler::class)->handleSuccessfulPayment($reference, $data);
    }
}
