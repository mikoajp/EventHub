<?php

namespace App\Tests\Service;

use App\DTO\PaymentResultDTO;
use App\Infrastructure\Payment\PaymentGatewayInterface;
use App\Service\PaymentService;
use PHPUnit\Framework\TestCase;

final class PaymentServiceTest extends TestCase
{
    public function testProcessPaymentReturnsDto(): void
    {
        $gateway = new class implements PaymentGatewayInterface {
            public function processPayment(string $paymentMethodId, int $amount, string $currency = 'USD', array $metadata = []): PaymentResultDTO
            {
                return new PaymentResultDTO(true, 'pi_test', 'ok');
            }
            public function refundPayment(string $paymentId, int $amount): PaymentResultDTO
            {
                return new PaymentResultDTO(true, 're_test', 'ok');
            }
            public function getPaymentStatus(string $paymentId): array
            {
                return ['id' => $paymentId, 'status' => 'succeeded'];
            }
            public function validatePaymentMethod(string $paymentMethodId): bool
            {
                return true;
            }
        };

        $service = new PaymentService($gateway);
        $result = $service->processPayment('pm_123', 1000, 'USD');

        $this->assertInstanceOf(PaymentResultDTO::class, $result);
        $this->assertTrue($result->success);
        $this->assertNotEmpty($result->paymentId);
    }
}