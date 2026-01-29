<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected $baseUrl = 'https://api.paystack.co';
    protected $secretKey;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key', env('PAYSTACK_SECRET_KEY'));
    }

    public function initializeTransaction($email, $amount, $reference, $callbackUrl = null)
    {
        // Amount is in kobo
        $response = Http::withToken($this->secretKey)->post("{$this->baseUrl}/transaction/initialize", [
            'email' => $email,
            'amount' => $amount * 100,
            'reference' => $reference,
            'callback_url' => $callbackUrl,
        ]);

        if ($response->successful()) {
            return $response->json()['data'];
        }

        Log::error('Paystack Initialize Error: ' . $response->body());
        return null;
    }

    public function verifyTransaction($reference)
    {
        $response = Http::withToken($this->secretKey)->get("{$this->baseUrl}/transaction/verify/{$reference}");

        if ($response->successful()) {
            return $response->json()['data'];
        }

        Log::error('Paystack Verify Error: ' . $response->body());
        return null;
    }
}
