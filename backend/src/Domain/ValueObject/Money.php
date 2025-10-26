<?php

namespace App\Domain\ValueObject;

use App\Exception\ValueObject\InvalidCurrencyException;

final class Money
{
    private const VALID_CURRENCIES = ['USD', 'EUR', 'GBP', 'PLN'];
    
    private int $amount; // minor units (e.g. cents)
    private string $currency;

    private function __construct(int $amount, string $currency)
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }
        
        $currency = strtoupper($currency);
        if (!in_array($currency, self::VALID_CURRENCIES, true)) {
            throw new \InvalidArgumentException('Invalid currency code');
        }
        
        $this->amount = $amount;
        $this->currency = $currency;
    }

    public static function fromInt(int $amount, string $currency = 'PLN'): self
    {
        if ($currency === '') {
            throw new InvalidCurrencyException($currency);
        }
        return new self($amount, $currency);
    }

    public static function fromString(string $amount, string $currency = 'PLN'): self
    {
        $normalized = (int) round(((float) $amount) * 100);
        return self::fromInt($normalized, $currency);
    }

    public function amount(): int { return $this->amount; }
    public function currency(): string { return $this->currency; }
    
    // Alias methods for backward compatibility with tests
    public function getAmount(): int { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }

    public function toFloat(): float { return $this->amount / 100.0; }
    
    public function format(int $decimals = 2): string
    {
        return number_format($this->toFloat(), $decimals).' '.$this->currency;
    }
    
    public function getFormatted(): string
    {
        return number_format($this->toFloat(), 2);
    }
    
    public function getFormattedWithSymbol(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'PLN' => 'zł',
        ];
        
        $symbol = $symbols[$this->currency] ?? $this->currency;
        return $symbol . number_format($this->toFloat(), 2);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }
    
    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot add money with different currencies');
        }
        
        return new self($this->amount + $other->amount, $this->currency);
    }
    
    public function subtract(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot subtract money with different currencies');
        }
        
        $result = $this->amount - $other->amount;
        if ($result < 0) {
            throw new \InvalidArgumentException('Result cannot be negative');
        }
        
        return new self($result, $this->currency);
    }
    
    public function multiply(int $multiplier): self
    {
        return new self($this->amount * $multiplier, $this->currency);
    }
    
    public function isLessThan(self $other): bool
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot compare money with different currencies');
        }
        
        return $this->amount < $other->amount;
    }
    
    public function isGreaterThan(self $other): bool
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot compare money with different currencies');
        }
        
        return $this->amount > $other->amount;
    }
}
