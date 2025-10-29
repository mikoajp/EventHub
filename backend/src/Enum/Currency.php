<?php

namespace App\Enum;

enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case PLN = 'PLN';

    /**
     * Get all currency values as strings
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn(self $currency) => $currency->value, self::cases());
    }

    /**
     * Get currency symbol
     */
    public function getSymbol(): string
    {
        return match($this) {
            self::USD => '$',
            self::EUR => '€',
            self::GBP => '£',
            self::PLN => 'zł',
        };
    }

    /**
     * Get currency display name
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::USD => 'US Dollar',
            self::EUR => 'Euro',
            self::GBP => 'British Pound',
            self::PLN => 'Polish Złoty',
        };
    }

    /**
     * Get number of decimal places
     */
    public function getDecimalPlaces(): int
    {
        return 2; // All supported currencies use 2 decimal places
    }

    /**
     * Format amount in cents to display string
     */
    public function formatAmount(int $amountInCents): string
    {
        $amount = $amountInCents / 100;
        return $this->getSymbol() . number_format($amount, $this->getDecimalPlaces());
    }
}
