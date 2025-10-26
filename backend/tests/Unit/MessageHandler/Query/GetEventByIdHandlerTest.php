<?php

namespace App\Tests\Unit\MessageHandler\Query;

use App\Message\Query\Event\GetEventByIdQuery;
use App\MessageHandler\Query\Event\GetEventByIdHandler;
use App\Repository\EventRepository;
use App\Infrastructure\Cache\CacheInterface;
use App\Entity\Event;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class GetEventByIdHandlerTest extends TestCase
{
    private EventRepository $eventRepository;
    private CacheInterface $cache;
    private GetEventByIdHandler $handler;

    protected function setUp(): void
    {
        $this->eventRepository = $this->createMock(EventRepository::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->handler = new GetEventByIdHandler(
            $this->eventRepository,
            $this->cache
        );
    }

    public function testHandlerReturnsCachedEvent(): void
    {
        $eventId = Uuid::v4()->toString();
        $event = $this->createMock(Event::class);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('event.' . $eventId)
            ->willReturn($event);

        $this->eventRepository
            ->expects($this->never())
            ->method('findByUuid');

        $query = new GetEventByIdQuery($eventId);
        $result = ($this->handler)($query);

        $this->assertSame($event, $result);
    }

    public function testHandlerFetchesFromRepositoryOnCacheMiss(): void
    {
        $eventId = Uuid::v4()->toString();
        $event = $this->createMock(Event::class);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->willReturnCallback(function($key, $callback) {
                return $callback();
            });

        $this->eventRepository
            ->expects($this->once())
            ->method('findByUuid')
            ->with($eventId)
            ->willReturn($event);

        $query = new GetEventByIdQuery($eventId);
        $result = ($this->handler)($query);

        $this->assertSame($event, $result);
    }

    public function testHandlerReturnsNullWhenEventNotFound(): void
    {
        $eventId = Uuid::v4()->toString();

        $this->cache
            ->method('get')
            ->willReturnCallback(function($key, $callback) {
                return $callback();
            });

        $this->eventRepository
            ->method('findByUuid')
            ->willReturn(null);

        $this->eventRepository
            ->method('find')
            ->willReturn(null);

        $query = new GetEventByIdQuery($eventId);
        $result = ($this->handler)($query);

        $this->assertNull($result);
    }

    public function testHandlerUsesCacheWithCorrectTTL(): void
    {
        $eventId = Uuid::v4()->toString();
        $event = $this->createMock(Event::class);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo('event.' . $eventId),
                $this->anything(),
                $this->equalTo(3600) // 1 hour TTL
            )
            ->willReturn($event);

        $query = new GetEventByIdQuery($eventId);
        ($this->handler)($query);
    }
}
