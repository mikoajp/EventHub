<?php

namespace App\Tests\Unit\Domain\Ticket;

use App\Domain\Ticket\Service\TicketAvailabilityChecker;
use App\Entity\Event;
use App\Entity\Ticket;
use App\Entity\TicketType;
use App\Repository\TicketRepository;
use PHPUnit\Framework\TestCase;

final class TicketAvailabilityCheckerTest extends TestCase
{
    private TicketRepository $ticketRepository;
    private TicketAvailabilityChecker $checker;

    protected function setUp(): void
    {
        $this->ticketRepository = $this->createMock(TicketRepository::class);
        $this->checker = new TicketAvailabilityChecker($this->ticketRepository);
    }

    public function testIsAvailableReturnsTrueWhenTicketsAvailable(): void
    {
        $ticketType = $this->createTicketType(100);
        
        $this->ticketRepository
            ->expects($this->once())
            ->method('count')
            ->with([
                'ticketType' => $ticketType,
                'status' => [Ticket::STATUS_PURCHASED, Ticket::STATUS_RESERVED]
            ])
            ->willReturn(50);

        $result = $this->checker->isAvailable($ticketType, 10);
        
        $this->assertTrue($result);
    }

    public function testIsAvailableReturnsFalseWhenNotEnoughTickets(): void
    {
        $ticketType = $this->createTicketType(100);
        
        $this->ticketRepository
            ->expects($this->once())
            ->method('count')
            ->with([
                'ticketType' => $ticketType,
                'status' => [Ticket::STATUS_PURCHASED, Ticket::STATUS_RESERVED]
            ])
            ->willReturn(95);

        $result = $this->checker->isAvailable($ticketType, 10);
        
        $this->assertFalse($result);
    }

    public function testIsAvailableReturnsTrueWhenExactlyEnoughTickets(): void
    {
        $ticketType = $this->createTicketType(100);
        
        $this->ticketRepository
            ->expects($this->once())
            ->method('count')
            ->willReturn(90);

        $result = $this->checker->isAvailable($ticketType, 10);
        
        $this->assertTrue($result);
    }

    public function testIsAvailableReturnsFalseWhenAllSold(): void
    {
        $ticketType = $this->createTicketType(100);
        
        $this->ticketRepository
            ->expects($this->once())
            ->method('count')
            ->willReturn(100);

        $result = $this->checker->isAvailable($ticketType, 1);
        
        $this->assertFalse($result);
    }

    public function testGetAvailableQuantityReturnsCorrectAmount(): void
    {
        $ticketType = $this->createTicketType(100);
        
        $this->ticketRepository
            ->expects($this->once())
            ->method('count')
            ->willReturn(35);

        $result = $this->checker->getAvailableQuantity($ticketType);
        
        $this->assertSame(65, $result);
    }

    public function testGetAvailableQuantityReturnsZeroWhenAllSold(): void
    {
        $ticketType = $this->createTicketType(50);
        
        $this->ticketRepository
            ->expects($this->once())
            ->method('count')
            ->willReturn(50);

        $result = $this->checker->getAvailableQuantity($ticketType);
        
        $this->assertSame(0, $result);
    }

    public function testGetAvailableQuantityNeverReturnsNegative(): void
    {
        $ticketType = $this->createTicketType(50);
        
        // Simulate overselling scenario
        $this->ticketRepository
            ->expects($this->once())
            ->method('count')
            ->willReturn(60);

        $result = $this->checker->getAvailableQuantity($ticketType);
        
        $this->assertSame(0, $result);
    }

    public function testCheckEventAvailabilityReturnsArrayForAllTicketTypes(): void
    {
        $event = $this->createMock(Event::class);
        
        $ticketType1 = $this->createTicketType(100, 'VIP');
        $ticketType2 = $this->createTicketType(200, 'Standard');
        
        $collection = new \Doctrine\Common\Collections\ArrayCollection([$ticketType1, $ticketType2]);
        $event->method('getTicketTypes')->willReturn($collection);
        
        // Mock count will be called 4 times: 2 for getAvailableQuantity, 2 for isAvailable
        $this->ticketRepository
            ->method('count')
            ->willReturnOnConsecutiveCalls(30, 30, 150, 150);

        $result = $this->checker->checkEventAvailability($event);
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey($ticketType1->getId()->toString(), $result);
        $this->assertArrayHasKey($ticketType2->getId()->toString(), $result);
    }

    public function testCheckEventAvailabilityContainsCorrectData(): void
    {
        $event = $this->createMock(Event::class);
        $ticketType = $this->createTicketType(100, 'VIP', 5000);
        
        $collection = new \Doctrine\Common\Collections\ArrayCollection([$ticketType]);
        $event->method('getTicketTypes')->willReturn($collection);
        
        $this->ticketRepository
            ->method('count')
            ->willReturn(40);

        $result = $this->checker->checkEventAvailability($event);
        
        $ticketData = $result[$ticketType->getId()->toString()];
        $this->assertSame('VIP', $ticketData['name']);
        $this->assertSame(5000, $ticketData['price']);
        $this->assertSame(100, $ticketData['total_quantity']);
        $this->assertSame(60, $ticketData['available_quantity']);
        $this->assertTrue($ticketData['is_available']);
    }

    public function testReserveTicketsReturnsTrueWhenAvailable(): void
    {
        $ticketType = $this->createTicketType(100);
        
        $this->ticketRepository
            ->method('count')
            ->willReturn(50);

        $result = $this->checker->reserveTickets($ticketType, 10);
        
        $this->assertTrue($result);
    }

    public function testReserveTicketsReturnsFalseWhenNotAvailable(): void
    {
        $ticketType = $this->createTicketType(100);
        
        $this->ticketRepository
            ->method('count')
            ->willReturn(95);

        $result = $this->checker->reserveTickets($ticketType, 10);
        
        $this->assertFalse($result);
    }

    private function createTicketType(int $quantity, string $name = 'Test Ticket', int $price = 1000): TicketType
    {
        $ticketType = $this->createMock(TicketType::class);
        $ticketType->method('getQuantity')->willReturn($quantity);
        $ticketType->method('getName')->willReturn($name);
        $ticketType->method('getPrice')->willReturn($price);
        $ticketType->method('getId')->willReturn(\Symfony\Component\Uid\Uuid::v4());
        
        return $ticketType;
    }
}
