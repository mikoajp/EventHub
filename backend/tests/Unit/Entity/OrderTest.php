<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Order;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function testCreateOrder(): void
    {
        $order = new Order();
        
        $this->assertInstanceOf(Order::class, $order);
    }

    public function testOrderHasId(): void
    {
        $order = new Order();
        
        // ID is generated, will be null until persisted
        $this->assertNull($order->getId());
    }

    public function testSetAndGetStatus(): void
    {
        $order = new Order();
        $order->setStatus('paid');
        
        $this->assertSame('paid', $order->getStatus());
    }

    public function testSetAndGetTotalAmount(): void
    {
        $order = new Order();
        $order->setTotalAmount(10000);
        
        $this->assertSame(10000, $order->getTotalAmount());
    }

    public function testOrderItemsCollectionInitialized(): void
    {
        $order = new Order();
        
        $this->assertNotNull($order->getOrderItems());
        $this->assertCount(0, $order->getOrderItems());
    }

    public function testSetAndGetCreatedAt(): void
    {
        $order = new Order();
        $date = new \DateTimeImmutable();
        $order->setCreatedAt($date);
        
        $this->assertSame($date, $order->getCreatedAt());
    }

    public function testOrderStatusConstants(): void
    {
        $this->assertSame('pending', Order::STATUS_PENDING);
        $this->assertSame('paid', Order::STATUS_PAID);
        $this->assertSame('cancelled', Order::STATUS_CANCELLED);
        $this->assertSame('refunded', Order::STATUS_REFUNDED);
    }
}
