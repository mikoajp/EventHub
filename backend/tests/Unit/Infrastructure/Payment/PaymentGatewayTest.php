<?php

namespace App\Tests\Unit\Infrastructure\Payment;

use App\Infrastructure\Payment\PaymentGatewayInterface;
use App\Infrastructure\Payment\StripePaymentGateway;
use PHPUnit\Framework\TestCase;

final class PaymentGatewayTest extends TestCase
{
    public function testPaymentGatewayInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(PaymentGatewayInterface::class));
    }

    public function testStripePaymentGatewayExists(): void
    {
        $this->assertTrue(class_exists(StripePaymentGateway::class));
    }

    public function testStripePaymentGatewayImplementsInterface(): void
    {
        $reflection = new \ReflectionClass(StripePaymentGateway::class);
        
        $this->assertTrue(
            $reflection->implementsInterface(PaymentGatewayInterface::class),
            'StripePaymentGateway should implement PaymentGatewayInterface'
        );
    }

    public function testPaymentGatewayInterfaceHasRequiredMethods(): void
    {
        $reflection = new \ReflectionClass(PaymentGatewayInterface::class);
        
        $this->assertTrue($reflection->hasMethod('processPayment'));
        $this->assertTrue($reflection->hasMethod('refundPayment'));
        $this->assertTrue($reflection->hasMethod('getPaymentStatus'));
        $this->assertTrue($reflection->hasMethod('validatePaymentMethod'));
    }

    public function testStripePaymentGatewayHasAllInterfaceMethods(): void
    {
        $reflection = new \ReflectionClass(StripePaymentGateway::class);
        
        $this->assertTrue($reflection->hasMethod('processPayment'));
        $this->assertTrue($reflection->hasMethod('refundPayment'));
        $this->assertTrue($reflection->hasMethod('getPaymentStatus'));
        $this->assertTrue($reflection->hasMethod('validatePaymentMethod'));
    }
}
