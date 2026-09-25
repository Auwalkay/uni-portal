<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Hostel;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SquadcoService implements PaymentGatewayInterface
{
    protected $baseUrl;
    protected $secretKey;

    public function __construct(?string $secretKey = null)
    {
        $this->baseUrl = config('services.squadco.base_url', env('SQUADCO_BASE_URL', 'https://sandbox-api-d.squadco.com'));
        $this->secretKey = $secretKey ?: config('services.squadco.secret_key', env('SQUADCO_SECRET_KEY'));
    }

    public function setSecretKey(?string $secretKey): self
    {
        if (!empty($secretKey)) {
            $this->secretKey = trim($secretKey);
        }
        return $this;
    }

    public function getSecretKey(): ?string
    {
        return $this->secretKey;
    }

    public static function forHostel(?Hostel $hostel): self
    {
        /** @var self $service */
        $service = \App\Services\Payment\PaymentGatewayFactory::resolveForHostel($hostel, 'squadco');
        return $service;
    }

    public static function forInvoice(?Invoice $invoice): self
    {
        /** @var self $service */
        $service = \App\Services\Payment\PaymentGatewayFactory::resolveForInvoice($invoice, 'squadco');
        return $service;
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
            'secret_key_used' => substr($this->secretKey ?? '', 0, 8) . '***',
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
            'secret_key_used' => substr($this->secretKey ?? '', 0, 8) . '***',
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

            $rawStatus = strtolower((string) ($data['transaction_status'] ?? $data['status'] ?? 'pending'));
            $normalizedStatus = in_array($rawStatus, ['success', 'successful', 'approved', 'completed', 'paid']) ? 'success' : $rawStatus;

            return [
                'status' => $normalizedStatus,
                'reference' => $data['transaction_ref'] ?? $reference,
                'amount' => $amountInNaira,
                'channel' => $data['transaction_type'] ?? $data['payment_method'] ?? 'squadco',
                'paid_at' => $data['created_at'] ?? $data['transaction_date'] ?? $data['paid_at'] ?? null,
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
