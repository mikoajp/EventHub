<?php

namespace App\Infrastructure\Payment;

use App\Enum\Currency;

final readonly class PaymentConfiguration
{
    /**
     * @param Currency $defaultCurrency Default currency for payments
     * @param array<Currency> $supportedCurrencies List of supported currencies
     * @param int $maxPaymentAmount Maximum payment amount in cents
     * @param int $minPaymentAmount Minimum payment amount in cents
     * @param array<string, mixed> $feeStructure Fee structure by currency
     */
    public function __construct(
        private Currency $defaultCurrency = Currency::USD,
        private array $supportedCurrencies = [],
        private int $maxPaymentAmount = 1000000, // $10,000 in cents
        private int $minPaymentAmount = 1, // $0.01 in cents
        private array $feeStructure = []
    ) {
        if (empty($this->supportedCurrencies)) {
            $this->supportedCurrencies = [
                Currency::USD,
                Currency::EUR,
                Currency::GBP,
                Currency::PLN,
            ];
        }

        if (empty($this->feeStructure)) {
            $this->feeStructure = $this->getDefaultFeeStructure();
        }
    }

    public function getDefaultCurrency(): Currency
    {
        return $this->defaultCurrency;
    }

    /**
     * @return array<Currency>
     */
    public function getSupportedCurrencies(): array
    {
        return $this->supportedCurrencies;
    }

    public function isCurrencySupported(Currency $currency): bool
    {
        return in_array($currency, $this->supportedCurrencies, true);
    }

    public function getMaxPaymentAmount(): int
    {
        return $this->maxPaymentAmount;
    }

    public function getMinPaymentAmount(): int
    {
        return $this->minPaymentAmount;
    }

    public function isAmountValid(int $amount): bool
    {
        return $amount >= $this->minPaymentAmount && $amount <= $this->maxPaymentAmount;
    }

    /**
     * Get fee percentage for a currency
     */
    public function getFeePercentage(Currency $currency): float
    {
        return $this->feeStructure[$currency->value]['percentage'] ?? 0.029;
    }

    /**
     * Get fixed fee for a currency (in cents)
     */
    public function getFixedFee(Currency $currency): int
    {
        return $this->feeStructure[$currency->value]['fixed'] ?? 30;
    }

    /**
     * Calculate total fees for an amount
     */
    public function calculateFees(int $amount, Currency $currency): array
    {
        $percentageFee = (int)($amount * $this->getFeePercentage($currency));
        $fixedFee = $this->getFixedFee($currency);
        $totalFees = $percentageFee + $fixedFee;

        return [
            'percentage_fee' => $percentageFee,
            'fixed_fee' => $fixedFee,
            'total_fees' => $totalFees,
            'net_amount' => $amount - $totalFees,
        ];
    }

    /**
     * Get default fee structure
     */
    private function getDefaultFeeStructure(): array
    {
        return [
            Currency::USD->value => [
                'percentage' => 0.029, // 2.9%
                'fixed' => 30, // $0.30
            ],
            Currency::EUR->value => [
                'percentage' => 0.025, // 2.5%
                'fixed' => 25, // €0.25
            ],
            Currency::GBP->value => [
                'percentage' => 0.025, // 2.5%
                'fixed' => 20, // £0.20
            ],
            Currency::PLN->value => [
                'percentage' => 0.035, // 3.5%
                'fixed' => 120, // 1.20 PLN
            ],
        ];
    }
}
