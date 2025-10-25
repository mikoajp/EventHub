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
         = new class implements PaymentGatewayInterface {
            public function processPayment(string , int , string  = 'USD', array  = []): PaymentResultDTO
            {
                return new PaymentResultDTO(true, 'pi_test', 'ok');
            }
            public function refundPayment(string , int ): PaymentResultDTO
            {
                return new PaymentResultDTO(true, 're_test', 'ok');
            }
            public function getPaymentStatus(string ): array
            {
                return ['id' => , 'status' => 'succeeded'];
            }
            public function validatePaymentMethod(string ): bool
            {
                return true;
            }
        };

         = new PaymentService();
         = ->processPayment('pm_123', 1000, 'USD');

        ->assertInstanceOf(PaymentResultDTO::class, );
        ->assertTrue(->success);
        ->assertNotEmpty(->paymentId);
    }
}
