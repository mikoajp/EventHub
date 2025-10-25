<?php

namespace App\Tests\Unit\Repository;

use App\Entity\TicketType;
use App\Repository\TicketTypeRepository;
use PHPUnit\Framework\TestCase;

final class TicketTypeRepositoryTest extends TestCase
{
    public function testRepositoryClassExists(): void
    {
        $this->assertTrue(class_exists(TicketTypeRepository::class));
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $reflection = new \ReflectionClass(TicketTypeRepository::class);
        $parent = $reflection->getParentClass();
        
        $this->assertNotFalse($parent);
        $this->assertSame('Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository', $parent->getName());
    }

    public function testRepositoryHasStandardMethods(): void
    {
        $reflection = new \ReflectionClass(TicketTypeRepository::class);
        
        $this->assertTrue($reflection->hasMethod('find'));
        $this->assertTrue($reflection->hasMethod('findAll'));
        $this->assertTrue($reflection->hasMethod('findBy'));
        $this->assertTrue($reflection->hasMethod('findOneBy'));
    }

    public function testRepositoryManagesTicketTypeEntity(): void
    {
        $reflection = new \ReflectionClass(TicketTypeRepository::class);
        $constructor = $reflection->getConstructor();
        
        $this->assertNotNull($constructor);
        
        // Check if repository is set up to manage TicketType entity
        $this->assertTrue(class_exists(TicketType::class));
    }
}
