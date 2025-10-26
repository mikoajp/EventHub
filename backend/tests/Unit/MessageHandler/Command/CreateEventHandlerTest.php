<?php

namespace App\Tests\Unit\MessageHandler\Command;

use App\Message\Command\Event\CreateEventCommand;
use App\Message\Event\EventCreatedEvent;
use App\MessageHandler\Command\Event\CreateEventHandler;
use App\Repository\UserRepository;
use App\Entity\User;
use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class CreateEventHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private MessageBusInterface $eventBus;
    private CreateEventHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->eventBus = $this->createMock(MessageBusInterface::class);

        $this->handler = new CreateEventHandler(
            $this->entityManager,
            $this->userRepository,
            $this->eventBus
        );
    }

    public function testHandlerCreatesEventSuccessfully(): void
    {
        $userId = Uuid::v4();
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($this->callback(function($arg) use ($userId) {
                return $arg instanceof Uuid && $arg->toString() === $userId->toString();
            }))
            ->willReturn($user);

        $capturedEvent = null;
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function($event) use (&$capturedEvent) {
                $capturedEvent = $event;
                // Simulate ID assignment after persist
                if ($event instanceof Event && !$event->getId()) {
                    $reflection = new \ReflectionClass($event);
                    $property = $reflection->getProperty('id');
                    $property->setAccessible(true);
                    $property->setValue($event, Uuid::v4());
                }
            });

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->eventBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(EventCreatedEvent::class))
            ->willReturn(new Envelope(new \stdClass()));

        $command = new CreateEventCommand(
            'Test Event',
            'Test Description',
            new \DateTimeImmutable('+1 month'),
            'Test Venue',
            100,
            $userId->toString(),
            []
        );

        $result = ($this->handler)($command);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertInstanceOf(Event::class, $capturedEvent);
    }

    public function testHandlerThrowsExceptionWhenUserNotFound(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Organizer not found');

        $command = new CreateEventCommand(
            'Test Event',
            'Test Description',
            new \DateTimeImmutable('+1 month'),
            'Test Venue',
            100,
            Uuid::v4()->toString(),
            []
        );

        ($this->handler)($command);
    }

    public function testHandlerPublishesEventCreatedEvent(): void
    {
        $userId = Uuid::v4();
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        $this->userRepository->method('find')->willReturn($user);
        
        $this->entityManager->method('persist')
            ->willReturnCallback(function($event) {
                if ($event instanceof Event && !$event->getId()) {
                    $reflection = new \ReflectionClass($event);
                    $property = $reflection->getProperty('id');
                    $property->setAccessible(true);
                    $property->setValue($event, Uuid::v4());
                }
            });
            
        $this->entityManager->method('flush');

        $capturedEvent = null;
        $this->eventBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function($event) use (&$capturedEvent) {
                $capturedEvent = $event;
                return new Envelope($event);
            });

        $command = new CreateEventCommand(
            'Test Event',
            'Test Description',
            new \DateTimeImmutable('+1 month'),
            'Test Venue',
            100,
            $userId->toString(),
            []
        );

        ($this->handler)($command);

        $this->assertInstanceOf(EventCreatedEvent::class, $capturedEvent);
    }
}
