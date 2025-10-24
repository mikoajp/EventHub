<?php

namespace App\Service;

use App\Infrastructure\Payment\PaymentGatewayInterface;

final class PaymentService
{
    public function __construct(private PaymentGatewayInterface $gateway) {}

    public function processPayment(string $paymentMethodId, int $amount, string $currency, array $metadata = []): PaymentResult
    {
        return $this->gateway->processPayment($paymentMethodId, $amount, $currency, $metadata);
    }
}
