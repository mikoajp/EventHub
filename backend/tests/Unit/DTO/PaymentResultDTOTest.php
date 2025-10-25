<?php

namespace App\Tests\Unit\DTO;

use App\DTO\PaymentResultDTO;
use PHPUnit\Framework\TestCase;

final class PaymentResultDTOTest extends TestCase
{
    public function testCreateSuccessfulPaymentResult(): void
    {
        $dto = new PaymentResultDTO(true, 'payment_123', 'succeeded');
        
        $this->assertTrue($dto->success);
        $this->assertSame('payment_123', $dto->paymentId);
        $this->assertSame('succeeded', $dto->message);
    }

    public function testCreateFailedPaymentResult(): void
    {
        $dto = new PaymentResultDTO(false, null, 'Payment declined');
        
        $this->assertFalse($dto->success);
        $this->assertNull($dto->paymentId);
        $this->assertSame('Payment declined', $dto->message);
    }

    public function testPaymentResultPropertiesArePublic(): void
    {
        $dto = new PaymentResultDTO(true, 'payment_456', 'completed');
        
        // Should be able to access public properties
        $this->assertIsBool($dto->success);
        $this->assertIsString($dto->paymentId);
        $this->assertIsString($dto->message);
    }

    public function testPaymentResultCanHaveNullPaymentId(): void
    {
        $dto = new PaymentResultDTO(false, null, 'error');
        
        $this->assertNull($dto->paymentId);
    }
}
