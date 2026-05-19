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
        // Amount is in kobo (cent)
        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/transaction/initiate", [
                'amount' => (int) ($amount * 100),
                'email' => $email,
                'currency' => 'NGN',
                'initiate_type' => 'inline',
                'transaction_ref' => $reference,
                'callback_url' => $callbackUrl,
                'customer_name' => $metadata['customer_name'] ?? null,
                'payment_channels' => $metadata['payment_channels'] ?? ['card', 'bank', 'ussd', 'transfer'],
                'metadata' => $metadata,
            ]);

        if ($response->successful()) {
            $data = $response->json()['data'];
            // Normalize to match Paystack pattern expected by controller
            return [
                'authorization_url' => $data['checkout_url'] ?? null,
                'reference' => $data['transaction_ref'] ?? $reference,
                'original_data' => $data
            ];
        }

        Log::error('Squadco Initialize Error: ' . $response->body());

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
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/transaction/verify/{$reference}");

        if ($response->successful()) {
            $data = $response->json()['data'];
            // Normalize to match Paystack pattern expected by controller
            return [
                'status' => $data['transaction_status'] ?? null,
                'reference' => $data['transaction_ref'] ?? null,
                'amount' => $data['amount'] ?? 0, // Keep in kobo to match Paystack pattern
                'channel' => $data['payment_method'] ?? 'squadco',
                'original_data' => $data
            ];
        }

        Log::error('Squadco Verify Error: ' . $response->body());

        return null;
    }
}
