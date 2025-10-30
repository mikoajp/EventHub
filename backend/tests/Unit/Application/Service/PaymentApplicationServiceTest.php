<?php

namespace App\Tests\Unit\Application\Service;

use App\Application\Service\PaymentApplicationService;
use App\Domain\Payment\Service\PaymentDomainService;
use App\DTO\PaymentResultDTO;
use App\Infrastructure\Payment\PaymentConfiguration;
use App\Infrastructure\Payment\PaymentGatewayInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class PaymentApplicationServiceTest extends TestCase
{
    private PaymentDomainService $domain;
    private PaymentGatewayInterface $gateway;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $config = new PaymentConfiguration();
        $this->domain = new PaymentDomainService($config);
        $this->gateway = new class implements PaymentGatewayInterface {
            public function processPayment(string $paymentMethodId, int $amount, string $currency = 'USD', array $metadata = []): PaymentResultDTO
            {
                if ($paymentMethodId === 'invalid') {
                    return new PaymentResultDTO(false, null, 'invalid method');
                }
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
                return $paymentMethodId !== 'invalid';
            }
        };
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testProcessPaymentSuccess(): void
    {
        $svc = new PaymentApplicationService($this->domain, $this->gateway, $this->logger);
        $res = $svc->processPayment('pm_ok', 1000, 'USD');
        $this->assertTrue($res->success);
        $this->assertNotNull($res->paymentId);
    }

    public function testProcessPaymentInvalidMethod(): void
    {
        $svc = new PaymentApplicationService($this->domain, $this->gateway, $this->logger);
        $res = $svc->processPayment('invalid', 1000, 'USD');
        $this->assertFalse($res->success);
        $this->assertNull($res->paymentId);
        $this->assertStringContainsString('Invalid payment method', $res->message);
    }

    public function testProcessPaymentInvalidCurrencyHandled(): void
    {
        $svc = new PaymentApplicationService($this->domain, $this->gateway, $this->logger);
        $res = $svc->processPayment('pm_ok', 1000, 'XYZ');
        $this->assertFalse($res->success);
        $this->assertNull($res->paymentId);
        $this->assertStringContainsString('Unsupported currency', $res->message);
    }

    public function testRefundAllowedWithinPeriod(): void
    {
        $svc = new PaymentApplicationService($this->domain, $this->gateway, $this->logger);
        $res = $svc->refundPayment('pi_1', 500, new \DateTimeImmutable('-10 days'));
        $this->assertTrue($res->success);
    }

    public function testRefundRejectedAfterDeadline(): void
    {
        $svc = new PaymentApplicationService($this->domain, $this->gateway, $this->logger);
        $res = $svc->refundPayment('pi_1', 500, new \DateTimeImmutable('-60 days'));
        $this->assertFalse($res->success);
        $this->assertStringContainsString('refund', strtolower($res->message));
    }
}
