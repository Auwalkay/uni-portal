<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Contracts\PaymentGatewayInterface;

class PaystackService implements PaymentGatewayInterface
{
    protected $baseUrl = 'https://api.paystack.co';

    protected $secretKey;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key', env('PAYSTACK_SECRET_KEY'));
    }

    public function initializeTransaction($email, $amount, $reference, $callbackUrl = null, array $metadata = [])
    {
        $payload = [
            'email' => $email,
            'amount' => (int) ($amount * 100),
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => $metadata,
        ];

        Log::info('[PAYMENT_INITIATE_REQUEST] [Paystack]', [
            'url' => "{$this->baseUrl}/transaction/initialize",
            'method' => 'POST',
            'exact_payload' => $payload,
        ]);

        $response = Http::withToken($this->secretKey)->post("{$this->baseUrl}/transaction/initialize", $payload);

        $rawResponseBody = $response->json() ?? $response->body();

        Log::info('[PAYMENT_INITIATE_RESPONSE] [Paystack]', [
            'reference' => $reference,
            'status_code' => $response->status(),
            'successful' => $response->successful(),
            'exact_response' => $rawResponseBody,
        ]);

        if ($response->successful()) {
            return $response->json()['data'] ?? [];
        }

        return null;
    }

    public function verifyTransaction($reference)
    {
        $url = "{$this->baseUrl}/transaction/verify/{$reference}";

        Log::info('[PAYMENT_REQUERY_REQUEST] [Paystack]', [
            'url' => $url,
            'method' => 'GET',
            'reference' => $reference,
        ]);

        $response = Http::withToken($this->secretKey)->get($url);

        $rawResponseBody = $response->json() ?? $response->body();

        Log::info('[PAYMENT_REQUERY_RESPONSE] [Paystack]', [
            'reference' => $reference,
            'status_code' => $response->status(),
            'successful' => $response->successful(),
            'exact_response' => $rawResponseBody,
        ]);

        if ($response->successful()) {
            return $response->json()['data'] ?? [];
        }

        $body = $response->json();
        if ($body && isset($body['message'])) {
            return [
                'status' => 'failed',
                'gateway_response' => $body['message'],
            ];
        }

        return null;
    }
}
