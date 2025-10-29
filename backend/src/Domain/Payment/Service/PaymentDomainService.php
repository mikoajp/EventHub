<?php

namespace App\Domain\Payment\Service;

use App\DTO\PaymentResultDTO;
use App\Enum\Currency;
use App\Exception\Payment\InvalidPaymentAmountException;
use App\Exception\Payment\UnsupportedCurrencyException;
use App\Infrastructure\Payment\PaymentConfiguration;

final readonly class PaymentDomainService
{
    public function __construct(
        private PaymentConfiguration $configuration
    ) {}

    public function validatePaymentAmount(int $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidPaymentAmountException($amount, 'Payment amount must be greater than zero');
        }

        if ($amount > $this->configuration->getMaxPaymentAmount()) {
            throw new InvalidPaymentAmountException($amount, 'Payment amount exceeds maximum limit');
        }

        if ($amount < $this->configuration->getMinPaymentAmount()) {
            throw new InvalidPaymentAmountException($amount, 'Payment amount is below minimum limit');
        }
    }

    public function validateCurrency(Currency|string $currency): void
    {
        // Convert string to Currency enum if needed
        if (is_string($currency)) {
            try {
                $currency = Currency::from($currency);
            } catch (\ValueError $e) {
                throw new UnsupportedCurrencyException($currency);
            }
        }
        
        if (!$this->configuration->isCurrencySupported($currency)) {
            throw new UnsupportedCurrencyException($currency->value);
        }
    }

    public function calculateFees(int $amount, Currency|string $currency = Currency::USD): array
    {
        // Convert string to Currency enum if needed
        if (is_string($currency)) {
            $currency = Currency::from($currency);
        }

        return $this->configuration->calculateFees($amount, $currency);
    }

    public function isRefundable(PaymentResultDTO $paymentResult, \DateTimeInterface $paymentDate): bool
    {
        if (!$paymentResult->success) {
            return false;
        }

        // Normalize to immutable for safe modification
        $immutableDate = $paymentDate instanceof \DateTimeImmutable
            ? $paymentDate
            : \DateTimeImmutable::createFromMutable($paymentDate);

        // Allow refunds within 30 days
        $refundDeadline = $immutableDate->modify('+30 days');
        
        return new \DateTimeImmutable() <= $refundDeadline;
    }

    /**
     * Get default currency
     */
    public function getDefaultCurrency(): Currency
    {
        return $this->configuration->getDefaultCurrency();
    }

    /**
     * Get all supported currencies
     * @return array<Currency>
     */
    public function getSupportedCurrencies(): array
    {
        return $this->configuration->getSupportedCurrencies();
    }
}