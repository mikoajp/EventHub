<?php

namespace App\Tests\Unit\DTO;

use App\DTO\PaymentResultDTO;
use PHPUnit\Framework\TestCase;

/**
 * Test that DTOs are truly immutable (readonly properties)
 */
final class DTOImmutabilityTest extends TestCase
{
    public function testPaymentResultDTOIsReadonly(): void
    {
        $dto = new PaymentResultDTO(
            success: true,
            paymentId: 'pi_123456',
            message: 'Payment successful'
        );

        $this->assertTrue($dto->success);
        $this->assertSame('pi_123456', $dto->paymentId);
        $this->assertSame('Payment successful', $dto->message);

        // Attempting to modify should cause an error in PHP 8.1+
        // This test documents the expected behavior
    }

    public function testPaymentResultDTOCannotBeModified(): void
    {
        $dto = new PaymentResultDTO(
            success: true,
            paymentId: 'pi_123456',
            message: 'Payment successful'
        );

        // Verify properties are readonly by checking class definition
        $reflection = new \ReflectionClass($dto);
        
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                "Property {$property->getName()} should be readonly"
            );
        }
    }

    public function testPaymentResultDTOHandlesFailure(): void
    {
        $dto = new PaymentResultDTO(
            success: false,
            paymentId: null,
            message: 'Insufficient funds'
        );

        $this->assertFalse($dto->success);
        $this->assertNull($dto->paymentId);
        $this->assertSame('Insufficient funds', $dto->message);
    }
}
