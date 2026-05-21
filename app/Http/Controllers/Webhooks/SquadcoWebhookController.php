<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SquadcoWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $signature = $request->header('x-squad-signature');
        $payload = $request->getContent();
        $secret = config('services.squadco.secret_key', env('SQUADCO_SECRET_KEY'));

        if (!$this->verifySignature($payload, $signature, $secret)) {
            Log::warning('Squadco Webhook Invalid Signature');
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $data = json_decode($payload, true);
        $event = $data['Event'] ?? $data['event'] ?? null;

        if ($event === 'charge_successful' || $event === 'charge.success') {
            $body = $data['Body'] ?? $data['body'] ?? [];
            $reference = $body['transaction_ref'] ?? $data['TransactionRef'] ?? null;
            
            if ($reference) {
                $this->processSuccessfulPayment($reference, $body);
            }
        }

        return response()->json(['message' => 'Webhook received']);
    }

    protected function verifySignature($payload, $signature, $secret)
    {
        if (!$signature || !$secret) return false;
        
        $computedSignature = strtoupper(hash_hmac('sha512', $payload, $secret));
        return hash_equals($computedSignature, strtoupper($signature));
    }

    protected function processSuccessfulPayment($reference, $data)
    {
        // Handle different possible keys for channel/method
        $data['channel'] = $data['transaction_type'] ?? $data['payment_method'] ?? 'squadco';
        
        app(\App\Services\Payment\PaymentHandler::class)->handleSuccessfulPayment($reference, $data);
    }
}
