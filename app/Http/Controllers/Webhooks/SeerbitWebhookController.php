<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SeerbitWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $signature = $request->header('x-seerbit-signature') ?? $request->header('X-Seerbit-Signature');
        $payload = $request->getContent();
        
        $data = json_decode($payload, true) ?: [];
        $paymentsData = $data['data']['payments'] ?? $data['data'] ?? $data;
        $reference = $paymentsData['paymentReference'] ?? $paymentsData['reference'] ?? $data['paymentReference'] ?? null;

        $secret = config('services.seerbit.secret_key', env('SEERBIT_SECRET_KEY'));
        
        if ($reference) {
            $payment = Payment::where('gateway_reference', $reference)->with('invoice')->first();
            if ($payment && $payment->invoice) {
                $service = \App\Services\Payment\PaymentGatewayFactory::resolveForInvoice($payment->invoice, 'seerbit');
                if ($service->getSecretKey()) {
                    $secret = $service->getSecretKey();
                }
            }
        }

        if (!$this->verifySignature($payload, $signature, $secret)) {
            Log::warning('Seerbit Webhook Invalid Signature', ['reference' => $reference]);
            // If signature is omitted in sandbox testing, fallback to verifying via SeerBit API query
            if ($reference) {
                return $this->verifyAndProcessDirectly($reference);
            }
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $code = $data['code'] ?? $paymentsData['code'] ?? null;
        $status = strtoupper((string) ($paymentsData['status'] ?? $data['status'] ?? ''));
        $event = strtoupper((string) ($data['notificationType'] ?? $data['event'] ?? ''));

        $isSuccessful = ($code === '00' || in_array($status, ['SUCCESS', 'SUCCESSFUL', 'APPROVED', 'COMPLETED']) || str_contains($event, 'SUCCESS'));

        if ($isSuccessful && $reference) {
            $this->processSuccessfulPayment($reference, $paymentsData);
        }

        return response()->json(['message' => 'Webhook received']);
    }

    protected function verifySignature($payload, $signature, $secret)
    {
        if (!$signature || !$secret) return false;
        
        $computedSha512 = hash_hmac('sha512', $payload, $secret);
        $computedSha256 = hash_hmac('sha256', $payload, $secret);

        return hash_equals(strtolower($computedSha512), strtolower($signature)) ||
               hash_equals(strtolower($computedSha256), strtolower($signature));
    }

    protected function processSuccessfulPayment($reference, $data)
    {
        $data['channel'] = $data['paymentType'] ?? 'seerbit';
        app(\App\Services\Payment\PaymentHandler::class)->handleSuccessfulPayment($reference, $data);
    }

    protected function verifyAndProcessDirectly(string $reference)
    {
        $result = app(\App\Services\Payment\PaymentHandler::class)->verifyAndProcessPayment($reference, null, 'seerbit');
        return response()->json(['message' => 'Verified directly', 'status' => $result['status']]);
    }
}
