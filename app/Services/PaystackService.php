<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Hostel;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService implements PaymentGatewayInterface
{
    protected $baseUrl = 'https://api.paystack.co';

    protected $secretKey;

    public function __construct(?string $secretKey = null)
    {
        $this->secretKey = $secretKey ?: config('services.paystack.secret_key', env('PAYSTACK_SECRET_KEY'));
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
        $service = \App\Services\Payment\PaymentGatewayFactory::resolveForHostel($hostel, 'paystack');
        return $service;
    }

    public static function forInvoice(?Invoice $invoice): self
    {
        /** @var self $service */
        $service = \App\Services\Payment\PaymentGatewayFactory::resolveForInvoice($invoice, 'paystack');
        return $service;
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
            'secret_key_used' => substr($this->secretKey ?? '', 0, 8) . '***',
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
            'secret_key_used' => substr($this->secretKey ?? '', 0, 8) . '***',
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
            $data = $response->json()['data'] ?? [];
            if (isset($data['status'])) {
                $rawStatus = strtolower((string) $data['status']);
                if (in_array($rawStatus, ['success', 'successful', 'approved', 'completed', 'paid'])) {
                    $data['status'] = 'success';
                } else {
                    $data['status'] = $rawStatus;
                }
            }
            return $data;
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
