<?php

namespace App\Domain\Payment\Service;

use App\DTO\PaymentResult;

final readonly class PaymentDomainService
{
    public function validatePaymentAmount(int $amount): void
    {
        if ($amount <= 0) {
            throw new \DomainException('Payment amount must be greater than zero');
        }

        if ($amount > 1000000) { // $10,000 limit
            throw new \DomainException('Payment amount exceeds maximum limit');
        }
    }

    public function validateCurrency(string $currency): void
    {
        $allowedCurrencies = ['USD', 'EUR', 'GBP', 'PLN'];
        
        if (!in_array($currency, $allowedCurrencies)) {
            throw new \DomainException('Unsupported currency: ' . $currency);
        }
    }

    public function calculateFees(int $amount, string $currency = 'USD'): array
    {
        // Different fee structures based on currency
        $feePercentage = match($currency) {
            'USD' => 0.029, // 2.9%
            'EUR' => 0.025, // 2.5%
            'GBP' => 0.025, // 2.5%
            'PLN' => 0.035, // 3.5%
            default => 0.029
        };

        $fixedFee = match($currency) {
            'USD' => 30, // $0.30
            'EUR' => 25, // €0.25
            'GBP' => 20, // £0.20
            'PLN' => 120, // 1.20 PLN
            default => 30
        };

        $percentageFee = (int)($amount * $feePercentage);
        $totalFees = $percentageFee + $fixedFee;

        return [
            'percentage_fee' => $percentageFee,
            'fixed_fee' => $fixedFee,
            'total_fees' => $totalFees,
            'net_amount' => $amount - $totalFees
        ];
    }

    public function isRefundable(PaymentResult $paymentResult, \DateTimeInterface $paymentDate): bool
    {
        if (!$paymentResult->success) {
            return false;
        }

        // Allow refunds within 30 days
        $refundDeadline = $paymentDate->modify('+30 days');
        
        return new \DateTimeImmutable() <= $refundDeadline;
    }
}