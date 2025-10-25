<?php

namespace App\Tests\Unit\Domain\Payment;

use App\Domain\Payment\Service\PaymentDomainService;
use App\DTO\PaymentResultDTO;
use PHPUnit\Framework\TestCase;

final class PaymentDomainServiceTest extends TestCase
{
    private PaymentDomainService $service;

    protected function setUp(): void
    {
        $this->service = new PaymentDomainService();
    }

    public function testServiceExists(): void
    {
        $this->assertInstanceOf(PaymentDomainService::class, $this->service);
    }

    public function testValidatePaymentAmountAcceptsValidAmount(): void
    {
        $this->service->validatePaymentAmount(5000);
        
        // No exception thrown
        $this->assertTrue(true);
    }

    public function testValidatePaymentAmountThrowsExceptionForZero(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Payment amount must be greater than zero');
        
        $this->service->validatePaymentAmount(0);
    }

    public function testValidatePaymentAmountThrowsExceptionForNegative(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Payment amount must be greater than zero');
        
        $this->service->validatePaymentAmount(-100);
    }

    public function testValidatePaymentAmountThrowsExceptionForExceedingMax(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Payment amount exceeds maximum limit');
        
        $this->service->validatePaymentAmount(1000001);
    }

    public function testValidateCurrencyAcceptsAllowedCurrencies(): void
    {
        $allowedCurrencies = ['USD', 'EUR', 'GBP', 'PLN'];
        
        foreach ($allowedCurrencies as $currency) {
            $this->service->validateCurrency($currency);
        }
        
        $this->assertTrue(true);
    }

    public function testValidateCurrencyThrowsExceptionForUnsupportedCurrency(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Unsupported currency');
        
        $this->service->validateCurrency('JPY');
    }

    public function testCalculateFeesForUSD(): void
    {
        $fees = $this->service->calculateFees(10000, 'USD');
        
        $this->assertIsArray($fees);
        $this->assertArrayHasKey('percentage_fee', $fees);
        $this->assertArrayHasKey('fixed_fee', $fees);
        $this->assertArrayHasKey('total_fees', $fees);
        $this->assertArrayHasKey('net_amount', $fees);
        
        $this->assertSame(290, $fees['percentage_fee']); // 2.9% of 10000
        $this->assertSame(30, $fees['fixed_fee']);
        $this->assertSame(320, $fees['total_fees']);
        $this->assertSame(9680, $fees['net_amount']);
    }

    public function testCalculateFeesForEUR(): void
    {
        $fees = $this->service->calculateFees(10000, 'EUR');
        
        $this->assertSame(250, $fees['percentage_fee']); // 2.5% of 10000
        $this->assertSame(25, $fees['fixed_fee']);
    }

    public function testCalculateFeesForPLN(): void
    {
        $fees = $this->service->calculateFees(10000, 'PLN');
        
        $this->assertSame(350, $fees['percentage_fee']); // 3.5% of 10000
        $this->assertSame(120, $fees['fixed_fee']);
    }

    public function testIsRefundableReturnsTrueForRecentSuccessfulPayment(): void
    {
        $paymentResult = new PaymentResultDTO(true, 'payment_123', 'succeeded');
        $paymentDate = new \DateTime('-10 days');
        
        $result = $this->service->isRefundable($paymentResult, $paymentDate);
        
        $this->assertTrue($result);
    }

    public function testIsRefundableReturnsFalseForFailedPayment(): void
    {
        $paymentResult = new PaymentResultDTO(false, null, 'failed');
        $paymentDate = new \DateTime('-10 days');
        
        $result = $this->service->isRefundable($paymentResult, $paymentDate);
        
        $this->assertFalse($result);
    }

    public function testIsRefundableReturnsFalseForOldPayment(): void
    {
        $paymentResult = new PaymentResultDTO(true, 'payment_123', 'succeeded');
        $paymentDate = new \DateTime('-40 days');
        
        $result = $this->service->isRefundable($paymentResult, $paymentDate);
        
        $this->assertFalse($result);
    }
}
