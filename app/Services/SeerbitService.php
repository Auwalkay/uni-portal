<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Hostel;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SeerbitService implements PaymentGatewayInterface
{
    protected string $baseUrl;
    protected ?string $secretKey;
    protected ?string $publicKey;

    public function __construct(?string $secretKey = null, ?string $publicKey = null)
    {
        $this->baseUrl = rtrim(config('services.seerbit.base_url', env('SEERBIT_BASE_URL', 'https://seerbitapi.com/api/v2')), '/');
        $this->secretKey = $secretKey ?: config('services.seerbit.secret_key', env('SEERBIT_SECRET_KEY'));
        $this->publicKey = $publicKey ?: config('services.seerbit.public_key', env('SEERBIT_PUBLIC_KEY'));
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

    public function setPublicKey(?string $publicKey): self
    {
        if (!empty($publicKey)) {
            $this->publicKey = trim($publicKey);
        }
        return $this;
    }

    public function getPublicKey(): ?string
    {
        return $this->publicKey;
    }

    public static function forHostel(?Hostel $hostel): self
    {
        /** @var self $service */
        $service = \App\Services\Payment\PaymentGatewayFactory::resolveForHostel($hostel, 'seerbit');
        return $service;
    }

    public static function forInvoice(?Invoice $invoice): self
    {
        /** @var self $service */
        $service = \App\Services\Payment\PaymentGatewayFactory::resolveForInvoice($invoice, 'seerbit');
        return $service;
    }

    /**
     * Obtain Bearer token from SeerBit API via POST /encrypt/keys.
     * Docs: https://doc.seerbit.com/online-payment/authentication
     */
    public function getBearerToken(): ?string
    {
        if (empty($this->secretKey) || empty($this->publicKey)) {
            Log::warning('[SEERBIT_AUTH_WARNING] Missing secretKey or publicKey');
            return null;
        }

        $url = "{$this->baseUrl}/encrypt/keys";
        $keyString = "{$this->secretKey}.{$this->publicKey}";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($url, [
                'key' => $keyString
            ]);

            if ($response->successful()) {
                $json = $response->json();
                $data = $json['data'] ?? [];
                $token = $data['EncryptedSecKey']['encryptedKey']
                    ?? $data['EncryptedKeys']['token']
                    ?? $data['token']
                    ?? null;

                if ($token) {
                    return $token;
                }
            }

            Log::warning('[SEERBIT_AUTH_FAILED]', [
                'status' => $response->status(),
                'response' => $response->json() ?? $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[SEERBIT_AUTH_ERROR]', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Initialize payment transaction via POST /api/v2/payments.
     * Docs: https://doc.seerbit.com/online-payment/payment-intiation
     */
    public function initializeTransaction($email, $amount, $reference, $callbackUrl = null, array $metadata = [])
    {
        $token = $this->getBearerToken();

        $payload = [
            'publicKey'          => $this->publicKey,
            'amount'             => number_format((float) $amount, 2, '.', ''),
            'currency'           => 'NGN',
            'country'            => 'NG',
            'paymentReference'   => $reference,
            'email'              => $email,
            'fullName'           => $metadata['customer_name'] ?? 'Student',
            'tokenize'           => 'false',
            'callbackUrl'        => $callbackUrl,
            'productDescription' => $metadata['payment_type'] ?? 'University Fee Payment',
            'customization'      => [
                'theme' => [
                    'border_color' => '#000000',
                    'background_color' => '#ffffff',
                    'button_color' => '#4F46E5',
                ]
            ],
            'metadata'           => $metadata,
        ];

        $primaryUrl = "{$this->baseUrl}/payments";

        Log::info('[PAYMENT_INITIATE_REQUEST] [Seerbit]', [
            'url' => $primaryUrl,
            'method' => 'POST',
            'secret_key_used' => substr($this->secretKey ?? '', 0, 8) . '***',
            'public_key_used' => substr($this->publicKey ?? '', 0, 8) . '***',
            'exact_payload' => $payload,
        ]);

        $request = Http::withHeaders(['Content-Type' => 'application/json']);
        if ($token) {
            $request = $request->withToken($token);
        } elseif ($this->secretKey) {
            $request = $request->withHeaders(['Authorization' => "Bearer {$this->secretKey}"]);
        }

        $response = $request->post($primaryUrl, $payload);

        // Handle 401 Unauthorized (common with test key environments) by retrying with Secret Key directly
        if ($response->status() === 401 && !empty($this->secretKey)) {
            Log::info('[PAYMENT_INITIATE_RETRY] 401 Unauthorized received. Retrying with Secret Key directly as Bearer token.');
            $directRequest = Http::withHeaders(['Content-Type' => 'application/json'])
                ->withToken($this->secretKey);
            $response = $directRequest->post($primaryUrl, $payload);
        }

        // Fallbacks if /payments returns 404 or FAILED status
        if ($response->status() === 404 || ($response->successful() && ($response->json()['status'] ?? '') === 'FAILED')) {
            $initiateUrl = "{$this->baseUrl}/payments/initiates";
            Log::info('[PAYMENT_INITIATE_FALLBACK] Retrying with /payments/initiates endpoint', ['url' => $initiateUrl]);
            $response = $request->post($initiateUrl, $payload);

            if ($response->status() === 404) {
                $merchantUrl = "{$this->baseUrl}/payments/merchant/initiates";
                Log::info('[PAYMENT_INITIATE_FALLBACK] Retrying with /payments/merchant/initiates endpoint', ['url' => $merchantUrl]);
                $response = $request->post($merchantUrl, $payload);
            }
        }

        $rawResponseBody = $response->json() ?? $response->body();

        Log::info('[PAYMENT_INITIATE_RESPONSE] [Seerbit]', [
            'reference' => $reference,
            'status_code' => $response->status(),
            'successful' => $response->successful(),
            'exact_response' => $rawResponseBody,
        ]);

        if ($response->successful()) {
            $json = $response->json();
            $data = $json['data']['payments'] ?? $json['data'] ?? [];
            $redirectUrl = $data['redirectLink']
                ?? $data['redirectUrl']
                ?? $data['checkoutUrl']
                ?? $data['url']
                ?? ($json['data']['payments']['redirectLink'] ?? null);

            if ($redirectUrl) {
                return [
                    'authorization_url' => $redirectUrl,
                    'reference' => $data['paymentReference'] ?? $reference,
                    'original_data' => $data,
                ];
            }
        }

        return null;
    }

    /**
     * Verify transaction status via GET /api/v3/payments/query/{reference}.
     * Docs: https://doc.seerbit.com/online-payment/after-payment/verify-payment
     */
    public function verifyTransaction($reference)
    {
        $token = $this->getBearerToken();
        $v3BaseUrl = str_replace('/api/v2', '/api/v3', $this->baseUrl);
        $url = "{$v3BaseUrl}/payments/query/{$reference}";

        Log::info('[PAYMENT_REQUERY_REQUEST] [Seerbit]', [
            'url' => $url,
            'method' => 'GET',
            'secret_key_used' => substr($this->secretKey ?? '', 0, 8) . '***',
            'reference' => $reference,
        ]);

        $request = Http::withHeaders(['Content-Type' => 'application/json']);
        if ($token) {
            $request = $request->withToken($token);
        } elseif ($this->secretKey) {
            $request = $request->withHeaders(['Authorization' => "Bearer {$this->secretKey}"]);
        }

        $response = $request->get($url);

        // Handle 401 Unauthorized by retrying with Secret Key directly
        if ($response->status() === 401 && !empty($this->secretKey)) {
            Log::info('[PAYMENT_REQUERY_RETRY] 401 Unauthorized received on query. Retrying with Secret Key directly as Bearer token.');
            $directRequest = Http::withHeaders(['Content-Type' => 'application/json'])
                ->withToken($this->secretKey);
            $response = $directRequest->get($url);
        }

        // Fallback to v2 endpoint if v3 returns 404
        if ($response->status() === 404) {
            $v2Url = "{$this->baseUrl}/payments/query/{$reference}";
            Log::info('[PAYMENT_REQUERY_FALLBACK] Retrying query with v2 endpoint', ['url' => $v2Url]);
            $response = $request->get($v2Url);

            if ($response->status() === 401 && !empty($this->secretKey)) {
                $directRequest = Http::withHeaders(['Content-Type' => 'application/json'])
                    ->withToken($this->secretKey);
                $response = $directRequest->get($v2Url);
            }
        }

        $rawResponseBody = $response->json() ?? $response->body();

        Log::info('[PAYMENT_REQUERY_RESPONSE] [Seerbit]', [
            'reference' => $reference,
            'status_code' => $response->status(),
            'successful' => $response->successful(),
            'exact_response' => $rawResponseBody,
        ]);

        if ($response->successful()) {
            $json = $response->json();
            $data = $json['data']['payments'] ?? $json['data'] ?? [];
            
            $gatewayCode = (string) ($data['gatewayCode'] ?? $data['code'] ?? $json['code'] ?? '');
            $rawMsg = $data['gatewayMessage'] ?? $data['message'] ?? $data['status'] ?? $data['paymentStatus'] ?? '';
            $gatewayMessage = strtolower((string) $rawMsg);

            $isFailed = in_array($gatewayMessage, ['failed', 'declined', 'cancelled', 'rejected', 's09', 'expired', 'unsuccessful'])
                || in_array(strtolower($gatewayCode), ['s09', 's20', '99']);

            $isSuccess = !$isFailed && (
                $gatewayCode === '00' 
                || in_array($gatewayMessage, ['successful', 'success', 'approved', 'completed', 'paid'])
            );

            $normalizedStatus = $isSuccess ? 'success' : ($isFailed ? 'failed' : 'pending');

            $userFriendlyResponse = match ($normalizedStatus) {
                'success' => !empty($data['gatewayMessage']) ? $data['gatewayMessage'] : 'Payment Successful',
                'failed'  => !empty($data['gatewayMessage']) ? $data['gatewayMessage'] : 'Payment failed or was cancelled at checkout.',
                'pending' => ($gatewayMessage === 'transaction is pending' || empty($data['gatewayMessage']))
                    ? 'Payment was not completed at checkout. Please try again.'
                    : $data['gatewayMessage'],
            };

            $amount = (float) ($data['amount'] ?? 0);

            return [
                'status' => $normalizedStatus,
                'reference' => $data['paymentReference'] ?? $data['gatewayref'] ?? $reference,
                'amount' => $amount,
                'channel' => $data['paymentType'] ?? 'card',
                'paid_at' => $data['transactionProcessedTime'] ?? $data['paidAt'] ?? $data['transactionStartDate'] ?? $data['updatedAt'] ?? $data['createdAt'] ?? null,
                'gateway_response' => $userFriendlyResponse,
                'original_data' => $data,
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

