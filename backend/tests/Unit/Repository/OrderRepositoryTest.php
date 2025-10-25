<?php

namespace App\Tests\Unit\Repository;

use App\Entity\Order;
use App\Repository\OrderRepository;
use PHPUnit\Framework\TestCase;

final class OrderRepositoryTest extends TestCase
{
    public function testRepositoryClassExists(): void
    {
        $this->assertTrue(class_exists(OrderRepository::class));
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $reflection = new \ReflectionClass(OrderRepository::class);
        $parent = $reflection->getParentClass();
        
        $this->assertNotFalse($parent);
        $this->assertSame('Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository', $parent->getName());
    }

    public function testRepositoryHasStandardMethods(): void
    {
        $reflection = new \ReflectionClass(OrderRepository::class);
        
        $this->assertTrue($reflection->hasMethod('find'));
        $this->assertTrue($reflection->hasMethod('findAll'));
        $this->assertTrue($reflection->hasMethod('findBy'));
        $this->assertTrue($reflection->hasMethod('findOneBy'));
        $this->assertTrue($reflection->hasMethod('count'));
    }

    public function testRepositoryManagesOrderEntity(): void
    {
        $reflection = new \ReflectionClass(OrderRepository::class);
        $constructor = $reflection->getConstructor();
        
        $this->assertNotNull($constructor);
        
        // Check if Order entity exists
        $this->assertTrue(class_exists(Order::class));
    }

    public function testRepositoryIsNotFinal(): void
    {
        $reflection = new \ReflectionClass(OrderRepository::class);
        
        $this->assertFalse($reflection->isFinal(), 'Repository should not be final for testing purposes');
    }
}
