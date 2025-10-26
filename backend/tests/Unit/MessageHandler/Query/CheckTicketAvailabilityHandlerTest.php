<?php

namespace App\Tests\Unit\MessageHandler\Query;

use App\Message\Query\Ticket\CheckTicketAvailabilityQuery;
use App\MessageHandler\Query\Ticket\CheckTicketAvailabilityHandler;
use App\Repository\TicketRepository;
use App\Infrastructure\Cache\CacheInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class CheckTicketAvailabilityHandlerTest extends TestCase
{
    private TicketRepository $ticketRepository;
    private CacheInterface $cache;
    private CheckTicketAvailabilityHandler $handler;

    protected function setUp(): void
    {
        $this->ticketRepository = $this->createMock(TicketRepository::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->handler = new CheckTicketAvailabilityHandler(
            $this->ticketRepository,
            $this->cache
        );
    }

    public function testHandlerReturnsCachedAvailability(): void
    {
        $eventId = Uuid::v4()->toString();
        $ticketTypeId = Uuid::v4()->toString();
        $quantity = 2;

        $expectedAvailability = [
            'available' => true,
            'remaining' => 10,
            'total' => 100
        ];

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with("ticket.availability.{$eventId}.{$ticketTypeId}.{$quantity}")
            ->willReturn($expectedAvailability);

        $this->ticketRepository
            ->expects($this->never())
            ->method('checkAvailability');

        $query = new CheckTicketAvailabilityQuery($eventId, $ticketTypeId, $quantity);
        $result = ($this->handler)($query);

        $this->assertEquals($expectedAvailability, $result);
    }

    public function testHandlerFetchesFromRepositoryOnCacheMiss(): void
    {
        $eventId = Uuid::v4()->toString();
        $ticketTypeId = Uuid::v4()->toString();
        $quantity = 2;

        $expectedAvailability = [
            'available' => true,
            'remaining' => 10,
            'total' => 100
        ];

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->willReturnCallback(function($key, $callback) {
                return $callback();
            });

        $this->ticketRepository
            ->expects($this->once())
            ->method('checkAvailability')
            ->with($eventId, $ticketTypeId, $quantity)
            ->willReturn($expectedAvailability);

        $query = new CheckTicketAvailabilityQuery($eventId, $ticketTypeId, $quantity);
        $result = ($this->handler)($query);

        $this->assertEquals($expectedAvailability, $result);
    }

    public function testHandlerUsesCorrectCacheTTL(): void
    {
        $eventId = Uuid::v4()->toString();
        $ticketTypeId = Uuid::v4()->toString();

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->equalTo(300) // 5 minutes
            )
            ->willReturn(['available' => true]);

        $query = new CheckTicketAvailabilityQuery($eventId, $ticketTypeId, 1);
        ($this->handler)($query);
    }

    public function testHandlerReturnsNotAvailableWhenSoldOut(): void
    {
        $eventId = Uuid::v4()->toString();
        $ticketTypeId = Uuid::v4()->toString();

        $expectedAvailability = [
            'available' => false,
            'remaining' => 0,
            'total' => 100
        ];

        $this->cache
            ->method('get')
            ->willReturnCallback(function($key, $callback) {
                return $callback();
            });

        $this->ticketRepository
            ->method('checkAvailability')
            ->willReturn($expectedAvailability);

        $query = new CheckTicketAvailabilityQuery($eventId, $ticketTypeId, 1);
        $result = ($this->handler)($query);

        $this->assertFalse($result['available']);
        $this->assertEquals(0, $result['remaining']);
    }
}
