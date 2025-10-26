<?php

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testCreateFromInt(): void
    {
        $money = Money::fromInt(10000, 'USD');
        
        $this->assertSame(10000, $money->amount());
        $this->assertSame('USD', $money->currency());
        $this->assertSame(100.0, $money->toFloat());
    }

    public function testCreateFromString(): void
    {
        $money = Money::fromString('99.99', 'USD');
        
        $this->assertSame(9999, $money->amount());
        $this->assertSame('USD', $money->currency());
        $this->assertSame(99.99, $money->toFloat());
    }

    public function testDefaultCurrency(): void
    {
        $money = Money::fromInt(5000);
        
        $this->assertSame('PLN', $money->currency());
    }

    public function testCurrencyIsNormalizedToUppercase(): void
    {
        $money = Money::fromInt(5000, 'usd');
        
        $this->assertSame('USD', $money->currency());
    }

    public function testFormat(): void
    {
        $money = Money::fromInt(12345, 'EUR');
        
        $this->assertSame('123.45 EUR', $money->format());
        $this->assertSame('123.5 EUR', $money->format(1));
        $this->assertSame('123 EUR', $money->format(0));
    }

    public function testEquality(): void
    {
        $money1 = Money::fromInt(10000, 'USD');
        $money2 = Money::fromInt(10000, 'USD');
        $money3 = Money::fromInt(10000, 'EUR');
        $money4 = Money::fromInt(20000, 'USD');
        
        $this->assertTrue($money1->equals($money2));
        $this->assertFalse($money1->equals($money3), 'Different currencies should not be equal');
        $this->assertFalse($money1->equals($money4), 'Different amounts should not be equal');
    }

    public function testEmptyCurrencyThrowsException(): void
    {
        $this->expectException(\App\Exception\ValueObject\InvalidCurrencyException::class);
        
        Money::fromInt(10000, '');
    }

    public function testStringConversionRounding(): void
    {
        $money = Money::fromString('99.995', 'USD');
        
        // Should round to nearest cent
        $this->assertSame(10000, $money->amount());
    }

    public function testZeroAmount(): void
    {
        $money = Money::fromInt(0, 'USD');
        
        $this->assertSame(0, $money->amount());
        $this->assertSame(0.0, $money->toFloat());
        $this->assertSame('0.00 USD', $money->format());
    }

    public function testNegativeAmount(): void
    {
        $money = Money::fromInt(-5000, 'USD');
        
        $this->assertSame(-5000, $money->amount());
        $this->assertSame(-50.0, $money->toFloat());
    }
}
