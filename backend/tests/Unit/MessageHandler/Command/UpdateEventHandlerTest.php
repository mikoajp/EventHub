<?php

namespace App\Tests\Unit\MessageHandler\Command;

use App\Message\Command\Event\UpdateEventCommand;
use App\MessageHandler\Command\Event\UpdateEventHandler;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use App\Domain\Event\Service\EventDomainService;
use App\Infrastructure\Cache\CacheInterface;
use App\Entity\Event;
use App\Entity\User;
use App\DTO\EventDTO;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class UpdateEventHandlerTest extends TestCase
{
    private EventRepository $eventRepository;
    private UserRepository $userRepository;
    private EventDomainService $eventDomainService;
    private EntityManagerInterface $entityManager;
    private CacheInterface $cache;
    private LoggerInterface $logger;
    private UpdateEventHandler $handler;

    protected function setUp(): void
    {
        $this->eventRepository = $this->createMock(EventRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->eventDomainService = $this->createMock(EventDomainService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new UpdateEventHandler(
            $this->eventRepository,
            $this->userRepository,
            $this->eventDomainService,
            $this->entityManager,
            $this->cache,
            $this->logger
        );
    }

    public function testHandlerUpdatesEventSuccessfully(): void
    {
        $eventId = Uuid::v4()->toString();
        $userId = Uuid::v4()->toString();
        
        $event = $this->createMock(Event::class);
        $event->method('getId')->willReturn(Uuid::fromString($eventId));
        
        $user = $this->createMock(User::class);
        
        $eventDTO = new EventDTO(
            'Updated Event',
            'Updated Description',
            new \DateTimeImmutable('+1 month'),
            'Updated Venue',
            200,
            []
        );

        $this->eventRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($event);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($user);

        $this->eventDomainService
            ->expects($this->once())
            ->method('canUserModifyEvent')
            ->with($event, $user)
            ->willReturn(true);

        $this->eventDomainService
            ->expects($this->once())
            ->method('updateEvent')
            ->with($event, $eventDTO);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with('event.' . $eventId);

        $this->cache
            ->expects($this->once())
            ->method('deletePattern')
            ->with('events.*');

        $command = new UpdateEventCommand($eventId, $userId, $eventDTO);
        ($this->handler)($command);
    }

    public function testHandlerThrowsExceptionWhenEventNotFound(): void
    {
        $this->eventRepository
            ->method('find')
            ->willReturn(null);

        $this->expectException(\App\Exception\Event\EventNotFoundException::class);

        $eventDTO = new EventDTO(
            'Updated Event',
            'Updated Description',
            new \DateTimeImmutable('+1 month'),
            'Updated Venue',
            200,
            []
        );

        $command = new UpdateEventCommand(
            Uuid::v4()->toString(),
            Uuid::v4()->toString(),
            $eventDTO
        );

        ($this->handler)($command);
    }

    public function testHandlerThrowsExceptionWhenUserHasNoPermission(): void
    {
        $event = $this->createMock(Event::class);
        $user = $this->createMock(User::class);

        $this->eventRepository->method('find')->willReturn($event);
        $this->userRepository->method('find')->willReturn($user);
        
        $this->eventDomainService
            ->method('canUserModifyEvent')
            ->willReturn(false);

        $this->expectException(\App\Exception\Authorization\InsufficientPermissionsException::class);

        $eventDTO = new EventDTO(
            'Updated Event',
            'Updated Description',
            new \DateTimeImmutable('+1 month'),
            'Updated Venue',
            200,
            []
        );

        $command = new UpdateEventCommand(
            Uuid::v4()->toString(),
            Uuid::v4()->toString(),
            $eventDTO
        );

        ($this->handler)($command);
    }

    public function testHandlerInvalidatesCacheAfterUpdate(): void
    {
        $eventId = Uuid::v4()->toString();
        $event = $this->createMock(Event::class);
        $event->method('getId')->willReturn(Uuid::fromString($eventId));
        $user = $this->createMock(User::class);

        $this->eventRepository->method('find')->willReturn($event);
        $this->userRepository->method('find')->willReturn($user);
        $this->eventDomainService->method('canUserModifyEvent')->willReturn(true);
        $this->entityManager->method('flush');

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with('event.' . $eventId);

        $this->cache
            ->expects($this->once())
            ->method('deletePattern')
            ->with('events.*');

        $eventDTO = new EventDTO(
            'Updated Event',
            'Updated Description',
            new \DateTimeImmutable('+1 month'),
            'Updated Venue',
            200,
            []
        );

        $command = new UpdateEventCommand($eventId, Uuid::v4()->toString(), $eventDTO);
        ($this->handler)($command);
    }
}
