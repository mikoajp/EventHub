<?php

namespace App\Tests\Unit\Repository;

use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Repository\QueryBuilder\TicketStatisticsQueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use PHPUnit\Framework\TestCase;

final class EventRepositorySortingTest extends TestCase
{
    public function testFindEventsWithFiltersHandlesSortingByKey(): void
    {
        // Create a mock entity manager
        $em = $this->createMock(EntityManagerInterface::class);
        
        // Create a mock query builder
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('addSelect')->willReturnSelf();
        $qb->method('groupBy')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('addOrderBy')->willReturnSelf();
        $qb->method('setFirstResult')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('distinct')->willReturnSelf();
        $qb->method('select')->willReturnSelf();
        
        // Create mock query
        $query = $this->createMock(AbstractQuery::class);
        $query->method('getSingleScalarResult')->willReturn(0);
        $query->method('getResult')->willReturn([]);
        
        $qb->method('getQuery')->willReturn($query);
        
        // Create a partial mock of EventRepository
        $ticketStatsQb = $this->createMock(TicketStatisticsQueryBuilder::class);
        
        $repository = $this->getMockBuilder(EventRepository::class)
            ->setConstructorArgs([
                $this->createMock(\Doctrine\Persistence\ManagerRegistry::class),
                $ticketStatsQb
            ])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
        
        $repository->method('createQueryBuilder')->willReturn($qb);
        
        // Test with 'by' key (which is what EventFiltersDTO returns)
        $sorting = ['by' => 'date', 'direction' => 'asc'];
        
        // This should not throw an exception
        $result = $repository->findEventsWithFilters(
            ['status' => ['published']],
            $sorting,
            1,
            10
        );
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('events', $result);
        $this->assertArrayHasKey('total', $result);
    }
    
    public function testFindEventsWithFiltersHandlesSortingFieldKey(): void
    {
        // Create a mock entity manager
        $em = $this->createMock(EntityManagerInterface::class);
        
        // Create a mock query builder
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('addSelect')->willReturnSelf();
        $qb->method('groupBy')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('addOrderBy')->willReturnSelf();
        $qb->method('setFirstResult')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('distinct')->willReturnSelf();
        $qb->method('select')->willReturnSelf();
        
        // Create mock query
        $query = $this->createMock(AbstractQuery::class);
        $query->method('getSingleScalarResult')->willReturn(0);
        $query->method('getResult')->willReturn([]);
        
        $qb->method('getQuery')->willReturn($query);
        
        // Create a partial mock of EventRepository
        $ticketStatsQb = $this->createMock(TicketStatisticsQueryBuilder::class);
        
        $repository = $this->getMockBuilder(EventRepository::class)
            ->setConstructorArgs([
                $this->createMock(\Doctrine\Persistence\ManagerRegistry::class),
                $ticketStatsQb
            ])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
        
        $repository->method('createQueryBuilder')->willReturn($qb);
        
        // Test with 'field' key (legacy support)
        $sorting = ['field' => 'name', 'direction' => 'desc'];
        
        // This should not throw an exception
        $result = $repository->findEventsWithFilters(
            ['status' => ['published']],
            $sorting,
            1,
            10
        );
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('events', $result);
    }
    
    public function testFindEventsWithFiltersUsesDefaultSorting(): void
    {
        // Create a mock entity manager
        $em = $this->createMock(EntityManagerInterface::class);
        
        // Create a mock query builder
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('addSelect')->willReturnSelf();
        $qb->method('groupBy')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('addOrderBy')->willReturnSelf();
        $qb->method('setFirstResult')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('distinct')->willReturnSelf();
        $qb->method('select')->willReturnSelf();
        
        // Create mock query
        $query = $this->createMock(AbstractQuery::class);
        $query->method('getSingleScalarResult')->willReturn(0);
        $query->method('getResult')->willReturn([]);
        
        $qb->method('getQuery')->willReturn($query);
        
        // Create a partial mock of EventRepository
        $ticketStatsQb = $this->createMock(TicketStatisticsQueryBuilder::class);
        
        $repository = $this->getMockBuilder(EventRepository::class)
            ->setConstructorArgs([
                $this->createMock(\Doctrine\Persistence\ManagerRegistry::class),
                $ticketStatsQb
            ])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
        
        $repository->method('createQueryBuilder')->willReturn($qb);
        
        // Test with empty sorting (should use defaults)
        $sorting = [];
        
        // This should not throw an exception and should use eventDate as default
        $result = $repository->findEventsWithFilters(
            [],
            $sorting,
            1,
            10
        );
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('events', $result);
    }
}
