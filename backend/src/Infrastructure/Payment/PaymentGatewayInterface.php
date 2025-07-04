<?php

namespace App\Infrastructure\Payment;

use App\DTO\PaymentResult;

interface PaymentGatewayInterface
{
    /**
     * Process a payment
     */
    public function processPayment(
        string $paymentMethodId,
        int $amount,
        string $currency = 'USD',
        array $metadata = []
    ): PaymentResult;

    /**
     * Process a refund
     */
    public function refundPayment(string $paymentId, int $amount): PaymentResult;

    /**
     * Get payment status
     */
    public function getPaymentStatus(string $paymentId): array;

    /**
     * Validate payment method
     */
    public function validatePaymentMethod(string $paymentMethodId): bool;
}