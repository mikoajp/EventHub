<?php

namespace App\Service;

use App\DTO\PaymentResult;

final readonly class PaymentService
{
    public function __construct(
        private string $stripeSecretKey,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function processPayment(
        string $paymentMethodId,
        int $amount,
        string $currency = 'USD',
        array $metadata = []
    ): PaymentResult {
        try {
            // Simulate Stripe payment processing
            $this->logger->info('Processing payment', [
                'payment_method_id' => $paymentMethodId,
                'amount' => $amount,
                'currency' => $currency,
                'metadata' => $metadata
            ]);

            usleep(500000); // 0.5 seconds

            $isSuccessful = rand(1, 100) <= 95;

            if ($isSuccessful) {
                $paymentId = 'pay_' . uniqid();
                
                return new PaymentResult(
                    success: true,
                    paymentId: $paymentId,
                    message: 'Payment processed successfully'
                );
            } else {
                return new PaymentResult(
                    success: false,
                    paymentId: null,
                    message: 'Payment failed - insufficient funds'
                );
            }

        } catch (\Exception $e) {
            $this->logger->error('Payment processing failed', [
                'error' => $e->getMessage(),
                'payment_method_id' => $paymentMethodId
            ]);

            return new PaymentResult(
                success: false,
                paymentId: null,
                message: 'Payment processing error: ' . $e->getMessage()
            );
        }
    }

    public function refundPayment(string $paymentId, int $amount): PaymentResult
    {
        try {
            $this->logger->info('Processing refund', [
                'payment_id' => $paymentId,
                'amount' => $amount
            ]);

            $refundId = 'refund_' . uniqid();

            return new PaymentResult(
                success: true,
                paymentId: $refundId,
                message: 'Refund processed successfully'
            );

        } catch (\Exception $e) {
            $this->logger->error('Refund processing failed', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId
            ]);

            return new PaymentResult(
                success: false,
                paymentId: null,
                message: 'Refund processing error: ' . $e->getMessage()
            );
        }
    }
}