<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SquadcoService implements PaymentGatewayInterface
{
    protected $baseUrl;
    protected $secretKey;

    public function __construct()
    {
        $this->baseUrl = config('services.squadco.base_url', env('SQUADCO_BASE_URL', 'https://sandbox-api-d.squadco.com'));
        $this->secretKey = config('services.squadco.secret_key', env('SQUADCO_SECRET_KEY'));
    }

    public function initializeTransaction($email, $amount, $reference, $callbackUrl = null, array $metadata = [])
    {
        $payload = [
            'amount' => (int) ($amount * 100),
            'email' => $email,
            'currency' => 'NGN',
            'initiate_type' => 'inline',
            'pass_charge' => true,
            'transaction_ref' => $reference,
            'callback_url' => $callbackUrl,
            'customer_name' => $metadata['customer_name'] ?? null,
            'payment_channels' => $metadata['payment_channels'] ?? ['card', 'bank', 'ussd', 'transfer'],
            'metadata' => $metadata,
        ];

        Log::info('[PAYMENT_INITIATE_REQUEST] [Squadco]', [
            'url' => "{$this->baseUrl}/transaction/initiate",
            'method' => 'POST',
            'exact_payload' => $payload,
        ]);

        $response = Http::withToken($this->secretKey)->post("{$this->baseUrl}/transaction/initiate", $payload);

        $rawResponseBody = $response->json() ?? $response->body();

        Log::info('[PAYMENT_INITIATE_RESPONSE] [Squadco]', [
            'reference' => $reference,
            'status_code' => $response->status(),
            'successful' => $response->successful(),
            'exact_response' => $rawResponseBody,
        ]);

        if ($response->successful()) {
            $data = $response->json()['data'] ?? [];
            return [
                'authorization_url' => $data['checkout_url'] ?? null,
                'reference' => $data['transaction_ref'] ?? $reference,
                'original_data' => $data
            ];
        }

        return null;
    }

    /**
     * Verify a transaction
     * 
     * @param string $reference
     * @return array|null
     */
    public function verifyTransaction($reference)
    {
        $url = "{$this->baseUrl}/transaction/verify/{$reference}";

        Log::info('[PAYMENT_REQUERY_REQUEST] [Squadco]', [
            'url' => $url,
            'method' => 'GET',
            'reference' => $reference,
        ]);

        $response = Http::withToken($this->secretKey)->get($url);

        $rawResponseBody = $response->json() ?? $response->body();

        Log::info('[PAYMENT_REQUERY_RESPONSE] [Squadco]', [
            'reference' => $reference,
            'status_code' => $response->status(),
            'successful' => $response->successful(),
            'exact_response' => $rawResponseBody,
        ]);

        if ($response->successful()) {
            $data = $response->json()['data'] ?? [];
            $amountInNaira = isset($data['transaction_amount']) 
                ? ($data['transaction_amount'] / 100) 
                : ($data['amount'] ?? 0);

            return [
                'status' => $data['transaction_status'] ?? 'pending',
                'reference' => $data['transaction_ref'] ?? $reference,
                'amount' => $amountInNaira,
                'channel' => $data['transaction_type'] ?? $data['payment_method'] ?? 'squadco',
                'gateway_response' => $data['transaction_status'] ?? 'Success',
                'original_data' => $data
            ];
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
