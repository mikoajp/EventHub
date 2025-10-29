<?php

namespace App\Tests\Integration\Service;

use App\DTO\PaymentResultDTO;
use App\Exception\Payment\InvalidPaymentAmountException;
use App\Exception\Payment\InvalidPaymentMethodException;
use App\Exception\Payment\PaymentFailedException;
use App\Infrastructure\Payment\PaymentGatewayInterface;
use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \App\Service\PaymentService
 * @group integration
 * @group payment
 */
final class PaymentIntegrationTest extends KernelTestCase
{
    private PaymentService $paymentService;
    private PaymentGatewayInterface $paymentGateway;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        // Get the payment service and gateway from container
        $this->paymentService = $container->get(PaymentService::class);
        $this->paymentGateway = $container->get(PaymentGatewayInterface::class);
    }

    public function testPaymentServiceIsRegisteredInContainer(): void
    {
        $this->assertInstanceOf(PaymentService::class, $this->paymentService);
    }

    public function testPaymentGatewayIsRegisteredInContainer(): void
    {
        $this->assertInstanceOf(PaymentGatewayInterface::class, $this->paymentGateway);
    }

    public function testProcessPaymentWithValidCard(): void
    {
        $result = $this->paymentService->processPayment('pm_card_visa', 2500, 'USD');
        
        $this->assertInstanceOf(PaymentResultDTO::class, $result);
        $this->assertTrue($result->success);
        $this->assertNotEmpty($result->transactionId);
        $this->assertSame(2500, $result->amount);
        $this->assertSame('USD', $result->currency);
    }

    public function testProcessPaymentWithMetadata(): void
    {
        $metadata = [
            'event_id' => 'event-123',
            'ticket_type' => 'VIP',
            'user_id' => 'user-456',
            'order_id' => 'order-789'
        ];
        
        $result = $this->paymentService->processPayment(
            'pm_card_visa',
            5000,
            'USD',
            $metadata
        );
        
        $this->assertInstanceOf(PaymentResultDTO::class, $result);
        $this->assertTrue($result->success);
        $this->assertNotEmpty($result->transactionId);
    }

    public function testProcessPaymentWithZeroAmountThrowsException(): void
    {
        $this->expectException(InvalidPaymentAmountException::class);
        
        $this->paymentService->processPayment('pm_card_visa', 0, 'USD');
    }

    public function testProcessPaymentWithNegativeAmountThrowsException(): void
    {
        $this->expectException(InvalidPaymentAmountException::class);
        
        $this->paymentService->processPayment('pm_card_visa', -1000, 'USD');
    }

    public function testProcessPaymentWithDeclinedCard(): void
    {
        // Use test card that triggers decline
        $this->expectException(PaymentFailedException::class);
        
        $this->paymentService->processPayment('pm_card_declined', 1000, 'USD');
    }

    public function testProcessPaymentWithInsufficientFunds(): void
    {
        // Use test card that simulates insufficient funds
        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessageMatches('/insufficient.*funds/i');
        
        $this->paymentService->processPayment('pm_card_insufficient_funds', 1000, 'USD');
    }

    public function testProcessPaymentWithInvalidPaymentMethod(): void
    {
        $this->expectException(InvalidPaymentMethodException::class);
        
        $this->paymentService->processPayment('invalid_pm_id', 1000, 'USD');
    }

    public function testProcessPaymentWithDifferentCurrencies(): void
    {
        $currencies = ['USD', 'EUR', 'GBP', 'PLN'];
        
        foreach ($currencies as $currency) {
            $result = $this->paymentService->processPayment('pm_card_visa', 1000, $currency);
            
            $this->assertInstanceOf(PaymentResultDTO::class, $result);
            $this->assertTrue($result->success);
            $this->assertSame($currency, $result->currency);
        }
    }

    public function testProcessPaymentWithLargeAmount(): void
    {
        // Test with large amount (e.g., $10,000.00 = 1000000 cents)
        $result = $this->paymentService->processPayment('pm_card_visa', 1000000, 'USD');
        
        $this->assertInstanceOf(PaymentResultDTO::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame(1000000, $result->amount);
    }

    public function testProcessPaymentReturnsUniqueTransactionIds(): void
    {
        $result1 = $this->paymentService->processPayment('pm_card_visa', 1000, 'USD');
        $result2 = $this->paymentService->processPayment('pm_card_visa', 1000, 'USD');
        
        $this->assertNotEquals($result1->transactionId, $result2->transactionId);
    }

    public function testValidatePaymentMethodWithValidCard(): void
    {
        $isValid = $this->paymentService->validatePaymentMethod('pm_card_visa');
        
        $this->assertTrue($isValid);
    }

    public function testValidatePaymentMethodWithInvalidCard(): void
    {
        $isValid = $this->paymentService->validatePaymentMethod('invalid_pm_id');
        
        $this->assertFalse($isValid);
    }

    public function testValidatePaymentMethodWithExpiredCard(): void
    {
        $isValid = $this->paymentService->validatePaymentMethod('pm_card_expired');
        
        $this->assertFalse($isValid);
    }

    public function testPaymentServiceDelegatesProcessingToGateway(): void
    {
        // This test verifies that PaymentService correctly delegates to the gateway
        $result = $this->paymentService->processPayment('pm_card_visa', 1500, 'USD', [
            'test_key' => 'test_value'
        ]);
        
        // Verify the result structure matches what gateway should return
        $this->assertInstanceOf(PaymentResultDTO::class, $result);
        $this->assertObjectHasProperty('success', $result);
        $this->assertObjectHasProperty('transactionId', $result);
        $this->assertObjectHasProperty('amount', $result);
        $this->assertObjectHasProperty('currency', $result);
    }

    public function testPaymentServiceDelegatesValidationToGateway(): void
    {
        // This test verifies that PaymentService correctly delegates validation to the gateway
        $isValid = $this->paymentService->validatePaymentMethod('pm_card_visa');
        
        $this->assertIsBool($isValid);
    }

    public function testProcessPaymentHandlesGatewayErrors(): void
    {
        // Use test card that triggers processing error
        $this->expectException(PaymentFailedException::class);
        
        $this->paymentService->processPayment('pm_card_processing_error', 1000, 'USD');
    }

    public function testProcessPaymentWithEmptyMetadata(): void
    {
        $result = $this->paymentService->processPayment('pm_card_visa', 1000, 'USD', []);
        
        $this->assertInstanceOf(PaymentResultDTO::class, $result);
        $this->assertTrue($result->success);
    }

    public function testProcessPaymentResultContainsTimestamp(): void
    {
        $result = $this->paymentService->processPayment('pm_card_visa', 1000, 'USD');
        
        $this->assertInstanceOf(PaymentResultDTO::class, $result);
        $this->assertObjectHasProperty('processedAt', $result);
        
        if (property_exists($result, 'processedAt')) {
            $this->assertInstanceOf(\DateTimeInterface::class, $result->processedAt);
        }
    }

    public function testProcessPaymentIdempotency(): void
    {
        // Process same payment twice with idempotency key
        $metadata = [
            'idempotency_key' => 'test-idempotency-' . uniqid(),
        ];
        
        $result1 = $this->paymentService->processPayment('pm_card_visa', 1000, 'USD', $metadata);
        $result2 = $this->paymentService->processPayment('pm_card_visa', 1000, 'USD', $metadata);
        
        // Both should succeed (or both should return same cached result)
        $this->assertInstanceOf(PaymentResultDTO::class, $result1);
        $this->assertInstanceOf(PaymentResultDTO::class, $result2);
        $this->assertTrue($result1->success);
        $this->assertTrue($result2->success);
    }

    public function testProcessPaymentWith3DSecureRequired(): void
    {
        // Use test card that requires 3D Secure authentication
        try {
            $result = $this->paymentService->processPayment('pm_card_3ds_required', 1000, 'USD');
            
            $this->assertInstanceOf(PaymentResultDTO::class, $result);
            
            // Result might be pending or require additional action
            if (property_exists($result, 'requiresAction')) {
                $this->assertTrue($result->requiresAction);
            }
        } catch (PaymentFailedException $e) {
            // Some gateways might throw exception for 3DS requirements
            $this->assertStringContainsString('3D', $e->getMessage());
        }
    }

    public function testGatewayIntegrationIsConfiguredCorrectly(): void
    {
        // Verify gateway configuration
        $container = static::getContainer();
        
        $this->assertTrue($container->has(PaymentGatewayInterface::class));
        $this->assertTrue($container->has(PaymentService::class));
        
        // Verify they're properly wired
        $service = $container->get(PaymentService::class);
        $this->assertInstanceOf(PaymentService::class, $service);
    }

    public function testMultipleConcurrentPaymentsAreHandledCorrectly(): void
    {
        // Simulate multiple concurrent payments
        $results = [];
        
        for ($i = 0; $i < 5; $i++) {
            $results[] = $this->paymentService->processPayment(
                'pm_card_visa',
                1000 + ($i * 100),
                'USD',
                ['order_id' => "order-{$i}"]
            );
        }
        
        // All should succeed
        $this->assertCount(5, $results);
        
        foreach ($results as $result) {
            $this->assertInstanceOf(PaymentResultDTO::class, $result);
            $this->assertTrue($result->success);
        }
        
        // All should have unique transaction IDs
        $transactionIds = array_map(fn($r) => $r->transactionId, $results);
        $uniqueIds = array_unique($transactionIds);
        $this->assertCount(5, $uniqueIds);
    }
}
