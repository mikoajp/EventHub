<?php

namespace App\Tests\Integration\Service;

use App\DTO\PaymentResultDTO;
use App\Infrastructure\Payment\PaymentGatewayInterface;
use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PaymentIntegrationTest extends KernelTestCase
{
    private PaymentService $paymentService;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        // Get the payment service from container
        $this->paymentService = $container->get(PaymentService::class);
    }

    public function testPaymentServiceIsRegistered(): void
    {
        $this->assertInstanceOf(PaymentService::class, $this->paymentService);
    }

    public function testPaymentServiceHasGatewayDependency(): void
    {
        $container = static::getContainer();
        $gateway = $container->get(PaymentGatewayInterface::class);
        
        $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
    }

    public function testProcessPaymentReturnsDTO(): void
    {
        // Test with a test payment method ID
        $result = $this->paymentService->processPayment('pm_test_card', 1000, 'USD');
        
        $this->assertInstanceOf(PaymentResultDTO::class, $result);
    }

    public function testProcessPaymentWithMetadata(): void
    {
        $metadata = [
            'event_id' => 'event-123',
            'ticket_type' => 'VIP',
            'user_id' => 'user-456'
        ];
        
        $result = $this->paymentService->processPayment(
            'pm_test_card',
            5000,
            'USD',
            $metadata
        );
        
        $this->assertInstanceOf(PaymentResultDTO::class, $result);
    }

    public function testValidatePaymentMethod(): void
    {
        $isValid = $this->paymentService->validatePaymentMethod('pm_test_card');
        
        $this->assertIsBool($isValid);
    }
}
