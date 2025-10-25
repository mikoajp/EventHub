<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class EventRepositorySmokeTest extends TestCase
{
    public function testRepositoryClassExists(): void
    {
        $this->assertTrue(class_exists(EventRepository::class), 'EventRepository class should exist');
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $reflection = new \ReflectionClass(EventRepository::class);
        $parent = $reflection->getParentClass();
        
        $this->assertNotFalse($parent, 'Repository should extend a parent class');
        $this->assertSame('Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository', $parent->getName());
    }

    public function testRepositoryHasRequiredDoctrineMethod(): void
    {
        $reflection = new \ReflectionClass(EventRepository::class);
        
        // Standard Doctrine repository methods
        $this->assertTrue($reflection->hasMethod('find'), 'Repository should have find() method');
        $this->assertTrue($reflection->hasMethod('findAll'), 'Repository should have findAll() method');
        $this->assertTrue($reflection->hasMethod('findBy'), 'Repository should have findBy() method');
        $this->assertTrue($reflection->hasMethod('findOneBy'), 'Repository should have findOneBy() method');
        $this->assertTrue($reflection->hasMethod('count'), 'Repository should have count() method');
    }

    public function testRepositoryHasCustomQueryMethods(): void
    {
        $reflection = new \ReflectionClass(EventRepository::class);
        
        // Custom query methods that should exist
        $expectedMethods = [
            'findPublishedEvents',
            'findByUuid',
            'findEventsWithFilters',
        ];
        
        foreach ($expectedMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                sprintf('Repository should have %s() method for custom queries', $method)
            );
        }
    }

    public function testRepositoryHasStatisticsMethods(): void
    {
        $reflection = new \ReflectionClass(EventRepository::class);
        
        // Statistics methods
        $statisticsMethods = [
            'getTicketSalesStatistics',
            'getRevenueStatistics',
            'getEventStatistics',
        ];
        
        foreach ($statisticsMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                sprintf('Repository should have %s() method for statistics', $method)
            );
        }
    }

    public function testRepositoryHasUtilityMethods(): void
    {
        $reflection = new \ReflectionClass(EventRepository::class);
        
        // Utility methods
        $utilityMethods = [
            'getUniqueVenues',
            'getPriceRange',
        ];
        
        foreach ($utilityMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                sprintf('Repository should have %s() method', $method)
            );
        }
    }

    public function testRepositoryConstructorAcceptsRegistry(): void
    {
        $reflection = new \ReflectionClass(EventRepository::class);
        $constructor = $reflection->getConstructor();
        
        $this->assertNotNull($constructor, 'Repository should have a constructor');
        
        $parameters = $constructor->getParameters();
        $this->assertCount(1, $parameters, 'Constructor should accept one parameter');
        
        $param = $parameters[0];
        // Parameter can be named 'doctrine', 'registry', or 'managerRegistry'
        $this->assertTrue(
            in_array($param->getName(), ['doctrine', 'registry', 'managerRegistry']),
            'Constructor parameter should be named doctrine, registry, or managerRegistry'
        );
        
        $type = $param->getType();
        $this->assertNotNull($type, 'Parameter should be type-hinted');
    }

    public function testRepositoryMethodsHaveCorrectReturnTypes(): void
    {
        $reflection = new \ReflectionClass(EventRepository::class);
        
        // Check findPublishedEvents returns array
        if ($reflection->hasMethod('findPublishedEvents')) {
            $method = $reflection->getMethod('findPublishedEvents');
            $returnType = $method->getReturnType();
            
            if ($returnType !== null) {
                $this->assertSame('array', $returnType->getName());
            }
        }
        
        // Check findByUuid can return Event or null
        if ($reflection->hasMethod('findByUuid')) {
            $method = $reflection->getMethod('findByUuid');
            $returnType = $method->getReturnType();
            
            $this->assertNotNull($returnType, 'findByUuid should have return type');
        }
    }

    public function testRepositoryIsNotFinal(): void
    {
        $reflection = new \ReflectionClass(EventRepository::class);
        
        $this->assertFalse($reflection->isFinal(), 'Repository should not be final to allow mocking in tests');
    }

    public function testRepositoryIsInstantiable(): void
    {
        $reflection = new \ReflectionClass(EventRepository::class);
        
        $this->assertTrue($reflection->isInstantiable(), 'Repository class should be instantiable');
        $this->assertFalse($reflection->isAbstract(), 'Repository should not be abstract');
        $this->assertFalse($reflection->isInterface(), 'Repository should not be an interface');
    }

    public function testRepositoryNamespace(): void
    {
        $reflection = new \ReflectionClass(EventRepository::class);
        
        $this->assertSame('App\Repository', $reflection->getNamespaceName());
        $this->assertSame('EventRepository', $reflection->getShortName());
    }
}
