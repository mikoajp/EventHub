<?php

namespace App\Tests\Unit\MessageHandler;

use App\Domain\Ticket\Service\TicketAvailabilityChecker;
use App\Entity\Event;
use App\Entity\TicketType;
use App\Entity\User;
use App\Message\Command\Ticket\PurchaseTicketCommand;
use App\MessageHandler\Command\Ticket\PurchaseTicketHandler;
use App\Repository\EventRepository;
use App\Repository\TicketTypeRepository;
use App\Repository\UserRepository;
use App\Service\IdempotencyService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class PurchaseTicketHandlerIdempotencyTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private EventRepository $eventRepository;
    private TicketTypeRepository $ticketTypeRepository;
    private UserRepository $userRepository;
    private TicketAvailabilityChecker $availabilityChecker;
    private MessageBusInterface $commandBus;
    private MessageBusInterface $eventBus;
    private IdempotencyService $idempotencyService;
    private LoggerInterface $logger;
    private PurchaseTicketHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->eventRepository = $this->createMock(EventRepository::class);
        $this->ticketTypeRepository = $this->createMock(TicketTypeRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->availabilityChecker = $this->createMock(TicketAvailabilityChecker::class);
        $this->commandBus = $this->createMock(MessageBusInterface::class);
        $this->eventBus = $this->createMock(MessageBusInterface::class);
        $this->idempotencyService = $this->createMock(IdempotencyService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new PurchaseTicketHandler(
            $this->entityManager,
            $this->eventRepository,
            $this->userRepository,
            $this->availabilityChecker,
            $this->commandBus,
            $this->eventBus,
            $this->idempotencyService,
            $this->logger
        );
    }

    public function testHandlerReturnsCachedResultForDuplicateCommand(): void
    {
        $command = new PurchaseTicketCommand(
            eventId: Uuid::v4()->toString(),
            ticketTypeId: Uuid::v4()->toString(),
            quantity: 2,
            userId: Uuid::v4()->toString(),
            paymentMethodId: 'pm_test123',
            idempotencyKey: 'unique-key-123'
        );

        $cachedResult = ['ticket-1', 'ticket-2'];

        $this->idempotencyService->expects($this->once())
            ->method('checkIdempotency')
            ->with('unique-key-123', PurchaseTicketCommand::class)
            ->willReturn($cachedResult);

        // Should not call any other services
        $this->entityManager->expects($this->never())->method('beginTransaction');
        $this->eventRepository->expects($this->never())->method('find');

        $result = ($this->handler)($command);

        $this->assertSame($cachedResult, $result);
    }

    public function testHandlerThrowsExceptionForConcurrentDuplicateRequest(): void
    {
        $command = new PurchaseTicketCommand(
            eventId: Uuid::v4()->toString(),
            ticketTypeId: Uuid::v4()->toString(),
            quantity: 2,
            userId: Uuid::v4()->toString(),
            paymentMethodId: 'pm_test123',
            idempotencyKey: 'processing-key-456'
        );

        $this->idempotencyService->expects($this->once())
            ->method('checkIdempotency')
            ->with('processing-key-456', PurchaseTicketCommand::class)
            ->willThrowException(new \App\Exception\Idempotency\CommandAlreadyProcessingException('processing-key-456', PurchaseTicketCommand::class));

        $this->expectException(\App\Exception\Idempotency\DuplicateRequestException::class);
        $this->expectExceptionMessage('Duplicate ticket purchase request detected');

        ($this->handler)($command);
    }

    public function testCommandGeneratesIdempotencyKeyWhenNotProvided(): void
    {
        $eventId = Uuid::v4()->toString();
        $ticketTypeId = Uuid::v4()->toString();
        $userId = Uuid::v4()->toString();
        $paymentMethodId = 'pm_test789';

        $command = new PurchaseTicketCommand(
            eventId: $eventId,
            ticketTypeId: $ticketTypeId,
            quantity: 1,
            userId: $userId,
            paymentMethodId: $paymentMethodId
            // No idempotencyKey provided
        );

        $generatedKey = $command->getIdempotencyKey();

        // Key should be deterministic
        $expectedKey = 'srv_' . hash('sha256', implode('|', [
            $eventId,
            $ticketTypeId,
            1,
            $userId,
            $paymentMethodId
        ]));

        $this->assertSame($expectedKey, $generatedKey);

        // Same inputs should generate same key
        $command2 = new PurchaseTicketCommand(
            eventId: $eventId,
            ticketTypeId: $ticketTypeId,
            quantity: 1,
            userId: $userId,
            paymentMethodId: $paymentMethodId
        );

        $this->assertSame($generatedKey, $command2->getIdempotencyKey());
    }

    public function testDifferentCommandParametersGenerateDifferentKeys(): void
    {
        $baseEventId = Uuid::v4()->toString();
        $baseTicketTypeId = Uuid::v4()->toString();
        $baseUserId = Uuid::v4()->toString();

        $command1 = new PurchaseTicketCommand(
            eventId: $baseEventId,
            ticketTypeId: $baseTicketTypeId,
            quantity: 1,
            userId: $baseUserId,
            paymentMethodId: 'pm_test1'
        );

        $command2 = new PurchaseTicketCommand(
            eventId: $baseEventId,
            ticketTypeId: $baseTicketTypeId,
            quantity: 2, // Different quantity
            userId: $baseUserId,
            paymentMethodId: 'pm_test1'
        );

        $this->assertNotSame(
            $command1->getIdempotencyKey(),
            $command2->getIdempotencyKey(),
            'Different quantities should generate different idempotency keys'
        );
    }
}
