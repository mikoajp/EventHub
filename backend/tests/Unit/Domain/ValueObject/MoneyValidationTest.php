<?php

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

/**
 * Test hard domain rules for Money: currency validation, rounding, negative amounts
 */
final class MoneyValidationTest extends TestCase
{
    public function testMoneyCannotBeNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount cannot be negative');

        Money::fromInt(-100, 'USD');
    }

    public function testMoneyRequiresValidCurrency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid currency code');

        Money::fromInt(1000, 'INVALID');
    }

    public function testMoneyAcceptsValidCurrencies(): void
    {
        $usd = Money::fromInt(1000, 'USD');
        $eur = Money::fromInt(2000, 'EUR');
        $gbp = Money::fromInt(3000, 'GBP');

        $this->assertSame(1000, $usd->getAmount());
        $this->assertSame('USD', $usd->getCurrency());
        $this->assertSame(2000, $eur->getAmount());
        $this->assertSame('EUR', $eur->getCurrency());
        $this->assertSame(3000, $gbp->getAmount());
        $this->assertSame('GBP', $gbp->getCurrency());
    }

    public function testMoneyFormatsCorrectly(): void
    {
        $money = Money::fromInt(12345, 'USD'); // $123.45

        $this->assertSame('123.45', $money->getFormatted());
        $this->assertSame('$123.45', $money->getFormattedWithSymbol());
    }

    public function testMoneyHandlesZeroAmount(): void
    {
        $money = Money::fromInt(0, 'USD');

        $this->assertSame(0, $money->getAmount());
        $this->assertSame('0.00', $money->getFormatted());
    }

    public function testMoneyCannotMixCurrencies(): void
    {
        $usd = Money::fromInt(1000, 'USD');
        $eur = Money::fromInt(1000, 'EUR');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot add money with different currencies');

        $usd->add($eur);
    }

    public function testMoneyAdditionWorks(): void
    {
        $money1 = Money::fromInt(1000, 'USD');
        $money2 = Money::fromInt(500, 'USD');

        $result = $money1->add($money2);

        $this->assertSame(1500, $result->getAmount());
        $this->assertSame('USD', $result->getCurrency());
    }

    public function testMoneySubtractionWorks(): void
    {
        $money1 = Money::fromInt(1000, 'USD');
        $money2 = Money::fromInt(300, 'USD');

        $result = $money1->subtract($money2);

        $this->assertSame(700, $result->getAmount());
    }

    public function testMoneySubtractionCannotResultInNegative(): void
    {
        $money1 = Money::fromInt(500, 'USD');
        $money2 = Money::fromInt(1000, 'USD');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Result cannot be negative');

        $money1->subtract($money2);
    }

    public function testMoneyMultiplication(): void
    {
        $money = Money::fromInt(1000, 'USD');

        $result = $money->multiply(3);

        $this->assertSame(3000, $result->getAmount());
    }

    public function testMoneyIsImmutable(): void
    {
        $original = Money::fromInt(1000, 'USD');
        $added = $original->add(Money::fromInt(500, 'USD'));

        // Original should not change
        $this->assertSame(1000, $original->getAmount());
        $this->assertSame(1500, $added->getAmount());
        $this->assertNotSame($original, $added);
    }

    public function testMoneyComparison(): void
    {
        $money1 = Money::fromInt(1000, 'USD');
        $money2 = Money::fromInt(1000, 'USD');
        $money3 = Money::fromInt(2000, 'USD');

        $this->assertTrue($money1->equals($money2));
        $this->assertFalse($money1->equals($money3));
        $this->assertTrue($money1->isLessThan($money3));
        $this->assertTrue($money3->isGreaterThan($money1));
    }
}
