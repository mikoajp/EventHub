<?php

namespace App\Tests\Unit\MessageHandler\Command;

use App\Message\Command\Ticket\CancelTicketCommand;
use App\MessageHandler\Command\Ticket\CancelTicketHandler;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use App\Domain\Ticket\Service\TicketDomainService;
use App\Infrastructure\Cache\CacheInterface;
use App\Entity\Ticket;
use App\Entity\User;
use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class CancelTicketHandlerTest extends TestCase
{
    private TicketRepository $ticketRepository;
    private UserRepository $userRepository;
    private TicketDomainService $ticketDomainService;
    private EntityManagerInterface $entityManager;
    private CacheInterface $cache;
    private LoggerInterface $logger;
    private CancelTicketHandler $handler;

    protected function setUp(): void
    {
        $this->ticketRepository = $this->createMock(TicketRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        // Create real TicketDomainService since it's final (it needs EntityManager)
        $this->ticketDomainService = new TicketDomainService($this->entityManager);

        $this->handler = new CancelTicketHandler(
            $this->ticketRepository,
            $this->userRepository,
            $this->ticketDomainService,
            $this->entityManager,
            $this->cache,
            $this->logger
        );
    }

    public function testHandlerCancelsTicketSuccessfully(): void
    {
        $ticketId = Uuid::v4()->toString();
        $userId = Uuid::v4()->toString();
        
        $user = $this->createMock(User::class);
        $ticket = $this->createMock(Ticket::class);
        $event = $this->createMock(Event::class);
        
        $event->method('getId')->willReturn(Uuid::v4());
        $ticket->method('getUser')->willReturn($user);
        $ticket->method('getEvent')->willReturn($event);

        $this->ticketRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($ticket);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($user);

        // TicketDomainService will be called (it's a real instance, not a mock)
        // Note: TicketDomainService.cancelTicket() calls flush() internally,
        // and handler also calls flush() - so flush is called twice
        $this->entityManager
            ->expects($this->exactly(2))
            ->method('flush');

        $this->cache
            ->expects($this->atLeastOnce())
            ->method('deletePattern');

        $command = new CancelTicketCommand($ticketId, $userId, 'Test reason');
        ($this->handler)($command);
    }

    public function testHandlerThrowsExceptionWhenTicketNotFound(): void
    {
        $this->ticketRepository
            ->method('find')
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ticket not found');

        $command = new CancelTicketCommand(
            Uuid::v4()->toString(),
            Uuid::v4()->toString(),
            null
        );

        ($this->handler)($command);
    }

    public function testHandlerThrowsExceptionWhenUserNotOwner(): void
    {
        $ticket = $this->createMock(Ticket::class);
        $ticketOwner = $this->createMock(User::class);
        $requestingUser = $this->createMock(User::class);

        $ticket->method('getUser')->willReturn($ticketOwner);

        $this->ticketRepository->method('find')->willReturn($ticket);
        $this->userRepository->method('find')->willReturn($requestingUser);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You can only cancel your own tickets');

        $command = new CancelTicketCommand(
            Uuid::v4()->toString(),
            Uuid::v4()->toString(),
            null
        );

        ($this->handler)($command);
    }

    public function testHandlerInvalidatesCacheAfterCancellation(): void
    {
        $eventId = Uuid::v4()->toString();
        $userId = Uuid::v4()->toString();
        
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(Uuid::fromString($userId));
        
        $event = $this->createMock(Event::class);
        $event->method('getId')->willReturn(Uuid::fromString($eventId));
        
        $ticket = $this->createMock(Ticket::class);
        $ticket->method('getUser')->willReturn($user);
        $ticket->method('getEvent')->willReturn($event);

        $this->ticketRepository->method('find')->willReturn($ticket);
        $this->userRepository->method('find')->willReturn($user);
        $this->entityManager->method('flush');

        $this->cache
            ->expects($this->once())
            ->method('deletePattern')
            ->with('ticket.availability.' . $eventId . '*');

        $this->cache
            ->expects($this->exactly(2))
            ->method('delete');

        $command = new CancelTicketCommand(
            Uuid::v4()->toString(),
            $userId,
            null
        );

        ($this->handler)($command);
    }
}
