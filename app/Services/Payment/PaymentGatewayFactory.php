<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Hostel;
use App\Models\Invoice;
use App\Models\SystemSetting;
use App\Services\PaystackService;
use App\Services\SquadcoService;

class PaymentGatewayFactory
{
    /**
     * Resolve the active payment gateway instance and gateway name for an invoice.
     *
     * @param Invoice|null $invoice
     * @param string|null $fallbackGateway
     * @return array [string $gatewayName, PaymentGatewayInterface $gatewayInstance]
     */
    public static function resolveWithGatewayName(?Invoice $invoice = null, ?string $fallbackGateway = null): array
    {
        $systemDefault = SystemSetting::get('payment_gateway', env('PAYMENT_GATEWAY', 'squadco'));
        $gatewayName = $fallbackGateway ?: $systemDefault;

        $hostel = $invoice?->getHostel();

        if ($hostel && !empty($hostel->payment_gateway)) {
            $gatewayName = $hostel->payment_gateway;
        }

        $service = self::resolveForHostel($hostel, $gatewayName);

        return [$gatewayName, $service];
    }

    /**
     * Resolve the payment gateway instance for an invoice.
     *
     * @param Invoice|null $invoice
     * @param string|null $fallbackGateway
     * @return PaymentGatewayInterface
     */
    public static function resolveForInvoice(?Invoice $invoice = null, ?string $fallbackGateway = null): PaymentGatewayInterface
    {
        [, $service] = self::resolveWithGatewayName($invoice, $fallbackGateway);
        return $service;
    }

    /**
     * Resolve the payment gateway instance for a hostel.
     *
     * @param Hostel|null $hostel
     * @param string|null $fallbackGateway
     * @return PaymentGatewayInterface
     */
    public static function resolveForHostel(?Hostel $hostel = null, ?string $fallbackGateway = null): PaymentGatewayInterface
    {
        $systemDefault = SystemSetting::get('payment_gateway', env('PAYMENT_GATEWAY', 'squadco'));
        $gatewayName = $fallbackGateway ?: ($hostel?->payment_gateway ?: $systemDefault);

        if ($gatewayName === 'paystack') {
            $service = new PaystackService();
            $secretKey = $hostel?->getSecretKeyForGateway('paystack');
            if ($secretKey) {
                $service->setSecretKey($secretKey);
            }
            return $service;
        }

        // Default to Squadco
        $service = new SquadcoService();
        $secretKey = $hostel?->getSecretKeyForGateway('squadco');
        if ($secretKey) {
            $service->setSecretKey($secretKey);
        }
        return $service;
    }
}
