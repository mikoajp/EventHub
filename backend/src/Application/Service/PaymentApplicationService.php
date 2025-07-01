<?php

namespace App\Application\Service;

use App\Domain\Payment\Service\PaymentDomainService;
use App\Infrastructure\Payment\PaymentGatewayInterface;
use App\DTO\PaymentResult;
use Psr\Log\LoggerInterface;

final readonly class PaymentApplicationService
{
    public function __construct(
        private PaymentDomainService $paymentDomainService,
        private PaymentGatewayInterface $paymentGateway,
        private LoggerInterface $logger
    ) {}

    public function processPayment(
        string $paymentMethodId,
        int $amount,
        string $currency = 'USD',
        array $metadata = []
    ): PaymentResult {
        try {
            // Domain validation
            $this->paymentDomainService->validatePaymentAmount($amount);
            $this->paymentDomainService->validateCurrency($currency);

            // Validate payment method
            if (!$this->paymentGateway->validatePaymentMethod($paymentMethodId)) {
                throw new \InvalidArgumentException('Invalid payment method');
            }

            // Calculate fees
            $feeCalculation = $this->paymentDomainService->calculateFees($amount, $currency);
            
            $this->logger->info('Processing payment with fees', [
                'original_amount' => $amount,
                'fees' => $feeCalculation,
                'currency' => $currency
            ]);

            // Process payment through gateway
            $result = $this->paymentGateway->processPayment(
                $paymentMethodId,
                $amount,
                $currency,
                array_merge($metadata, ['fees' => $feeCalculation])
            );

            if ($result->success) {
                $this->logger->info('Payment processed successfully', [
                    'payment_id' => $result->paymentId,
                    'amount' => $amount,
                    'currency' => $currency
                ]);
            } else {
                $this->logger->warning('Payment failed', [
                    'payment_method_id' => $paymentMethodId,
                    'amount' => $amount,
                    'reason' => $result->message
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Payment processing error', [
                'error' => $e->getMessage(),
                'payment_method_id' => $paymentMethodId,
                'amount' => $amount
            ]);

            return new PaymentResult(
                success: false,
                paymentId: null,
                message: 'Payment processing failed: ' . $e->getMessage()
            );
        }
    }

    public function refundPayment(
        string $paymentId,
        int $amount,
        \DateTimeInterface $originalPaymentDate
    ): PaymentResult {
        try {
            // Domain validation
            $this->paymentDomainService->validatePaymentAmount($amount);

            // Check if refund is allowed
            $originalPaymentResult = new PaymentResult(true, $paymentId, 'Original payment');
            if (!$this->paymentDomainService->isRefundable($originalPaymentResult, $originalPaymentDate)) {
                throw new \DomainException('Payment is not refundable (refund period expired)');
            }

            // Process refund through gateway
            $result = $this->paymentGateway->refundPayment($paymentId, $amount);

            if ($result->success) {
                $this->logger->info('Refund processed successfully', [
                    'refund_id' => $result->paymentId,
                    'original_payment_id' => $paymentId,
                    'amount' => $amount
                ]);
            } else {
                $this->logger->warning('Refund failed', [
                    'payment_id' => $paymentId,
                    'amount' => $amount,
                    'reason' => $result->message
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Refund processing error', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId,
                'amount' => $amount
            ]);

            return new PaymentResult(
                success: false,
                paymentId: null,
                message: 'Refund processing failed: ' . $e->getMessage()
            );
        }
    }

    public function getPaymentStatus(string $paymentId): array
    {
        try {
            return $this->paymentGateway->getPaymentStatus($paymentId);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get payment status', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);

            return [
                'error' => 'Failed to retrieve payment status',
                'message' => $e->getMessage()
            ];
        }
    }

    public function calculatePaymentFees(int $amount, string $currency = 'USD'): array
    {
        $this->paymentDomainService->validatePaymentAmount($amount);
        $this->paymentDomainService->validateCurrency($currency);

        return $this->paymentDomainService->calculateFees($amount, $currency);
    }
}