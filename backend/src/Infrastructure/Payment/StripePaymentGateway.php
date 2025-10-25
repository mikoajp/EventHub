<?php

namespace App\Infrastructure\Payment;

use App\DTO\PaymentResultDTO;
use Psr\Log\LoggerInterface;

final readonly class StripePaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function processPayment(
        string $paymentMethodId,
        int $amount,
        string $currency = 'USD',
        array $metadata = []
    ): PaymentResultDTO {
        try {
            $this->logger->info('Processing Stripe payment', [
                'payment_method_id' => $paymentMethodId,
                'amount' => $amount,
                'currency' => $currency,
                'metadata' => $metadata
            ]);

            usleep(500000);

            $isSuccessful = rand(1, 100) <= 95;

            if ($isSuccessful) {
                $paymentId = 'pi_' . uniqid();
                
                $this->logger->info('Stripe payment successful', [
                    'payment_id' => $paymentId,
                    'amount' => $amount
                ]);
                
                return new PaymentResultDTO(
                    success: true,
                    paymentId: $paymentId,
                    message: 'Payment processed successfully'
                );
            } else {
                $this->logger->warning('Stripe payment failed', [
                    'payment_method_id' => $paymentMethodId,
                    'reason' => 'insufficient_funds'
                ]);
                
                return new PaymentResultDTO(
                    success: false,
                    paymentId: null,
                    message: 'Payment failed - insufficient funds'
                );
            }

        } catch (\Exception $e) {
            $this->logger->error('Stripe payment processing failed', [
                'error' => $e->getMessage(),
                'payment_method_id' => $paymentMethodId
            ]);

            return new PaymentResultDTO(
                success: false,
                paymentId: null,
                message: 'Payment processing error: ' . $e->getMessage()
            );
        }
    }

    public function refundPayment(string $paymentId, int $amount): PaymentResultDTO
    {
        try {
            $this->logger->info('Processing Stripe refund', [
                'payment_id' => $paymentId,
                'amount' => $amount
            ]);

            usleep(300000);

            $refundId = 're_' . uniqid();

            $this->logger->info('Stripe refund successful', [
                'refund_id' => $refundId,
                'original_payment_id' => $paymentId,
                'amount' => $amount
            ]);

            return new PaymentResultDTO(
                success: true,
                paymentId: $refundId,
                message: 'Refund processed successfully'
            );

        } catch (\Exception $e) {
            $this->logger->error('Stripe refund processing failed', [
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

    public function getPaymentStatus(string $paymentId): array
    {
        return [
            'id' => $paymentId,
            'status' => 'succeeded',
            'amount' => 2000,
            'currency' => 'usd',
            'created' => time()
        ];
    }

    public function validatePaymentMethod(string $paymentMethodId): bool
    {
        return !empty($paymentMethodId) && str_starts_with($paymentMethodId, 'pm_');
    }
}