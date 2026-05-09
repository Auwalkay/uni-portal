<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    public function initializeTransaction($email, $amount, $reference, $callbackUrl = null, array $metadata = []);
    public function verifyTransaction($reference);
}
