<?php

namespace App\Domain\ValueObject;

final class Money
{
    private int $amount; // minor units (e.g. cents)
    private string $currency;

    private function __construct(int $amount, string $currency)
    {
        $this->amount = $amount;
        $this->currency = strtoupper($currency);
    }

    public static function fromInt(int $amount, string $currency = 'PLN'): self
    {
        if ($currency === '') {
            throw new \InvalidArgumentException('Currency required');
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

    public function toFloat(): float { return $this->amount / 100.0; }
    public function format(int $decimals = 2): string
    {
        return number_format($this->toFloat(), $decimals).' '.$this->currency;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }
}
