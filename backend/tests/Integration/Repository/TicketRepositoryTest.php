<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Event;
use App\Entity\Ticket;
use App\Entity\TicketType;
use App\Entity\User;
use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TicketRepositoryTest extends KernelTestCase
{
    private TicketRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->repository = $container->get(TicketRepository::class);
    }

    public function testRepositoryExists(): void
    {
        $this->assertInstanceOf(TicketRepository::class, $this->repository);
    }

    public function testFindByUser(): void
    {
        // This is a smoke test to ensure the repository method exists
        $user = $this->createMock(User::class);
        
        $result = $this->repository->findBy(['user' => $user]);
        
        $this->assertIsArray($result);
    }

    public function testCountByTicketTypeAndStatus(): void
    {
        $ticketType = $this->createMock(TicketType::class);
        
        $count = $this->repository->count([
            'ticketType' => $ticketType,
            'status' => [Ticket::STATUS_PURCHASED, Ticket::STATUS_RESERVED]
        ]);
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }
}
