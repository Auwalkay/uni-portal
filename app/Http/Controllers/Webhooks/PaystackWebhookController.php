<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();
        $secret = config('services.paystack.secret_key', env('PAYSTACK_SECRET_KEY'));

        if (!$this->verifySignature($payload, $signature, $secret)) {
            Log::warning('Paystack Webhook Invalid Signature');
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $data = json_decode($payload, true);
        $event = $data['event'] ?? null;

        if ($event === 'charge.success') {
            $body = $data['data'] ?? [];
            $reference = $body['reference'] ?? null;
            
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
