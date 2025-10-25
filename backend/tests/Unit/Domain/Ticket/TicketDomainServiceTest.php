<?php

namespace App\Tests\Unit\Domain\Ticket;

use App\Domain\Ticket\Service\TicketDomainService;
use App\Entity\Event;
use App\Entity\Ticket;
use App\Entity\TicketType;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class TicketDomainServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private TicketDomainService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->service = new TicketDomainService($this->entityManager);
    }

    public function testCreateTicketCreatesNewTicketWithCorrectStatus(): void
    {
        $user = $this->createMock(User::class);
        $event = $this->createMock(Event::class);
        $ticketType = $this->createMock(TicketType::class);
        $ticketType->method('getPrice')->willReturn(5000);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $ticket = $this->service->createTicket($user, $event, $ticketType);

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertSame(Ticket::STATUS_RESERVED, $ticket->getStatus());
        $this->assertSame(5000, $ticket->getPrice());
    }

    public function testConfirmTicketPurchaseChangesStatusToPurchased(): void
    {
        $ticket = new Ticket();
        $ticket->setStatus(Ticket::STATUS_RESERVED);

        $this->entityManager->expects($this->once())->method('flush');

        $this->service->confirmTicketPurchase($ticket, 'payment_123');

        $this->assertSame(Ticket::STATUS_PURCHASED, $ticket->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $ticket->getPurchasedAt());
    }

    public function testCancelTicketChangesStatusToCancelled(): void
    {
        $ticket = new Ticket();
        $ticket->setStatus(Ticket::STATUS_RESERVED);

        $this->entityManager->expects($this->once())->method('flush');

        $this->service->cancelTicket($ticket, 'Customer request');

        $this->assertSame(Ticket::STATUS_CANCELLED, $ticket->getStatus());
    }

    public function testRefundTicketChangesStatusToRefunded(): void
    {
        $ticket = new Ticket();
        $ticket->setStatus(Ticket::STATUS_PURCHASED);

        $this->entityManager->expects($this->once())->method('flush');

        $this->service->refundTicket($ticket, 'refund_123');

        $this->assertSame(Ticket::STATUS_REFUNDED, $ticket->getStatus());
    }

    public function testIsTicketTransferableReturnsTrueForPurchasedFutureEvent(): void
    {
        $ticket = new Ticket();
        $ticket->setStatus(Ticket::STATUS_PURCHASED);

        $event = $this->createMock(Event::class);
        $futureDate = new \DateTimeImmutable('+1 month');
        $event->method('getEventDate')->willReturn($futureDate);
        $ticket->setEvent($event);

        $result = $this->service->isTicketTransferable($ticket);

        $this->assertTrue($result);
    }

    public function testIsTicketTransferableReturnsFalseForPastEvent(): void
    {
        $ticket = new Ticket();
        $ticket->setStatus(Ticket::STATUS_PURCHASED);

        $event = $this->createMock(Event::class);
        $pastDate = new \DateTimeImmutable('-1 month');
        $event->method('getEventDate')->willReturn($pastDate);
        $ticket->setEvent($event);

        $result = $this->service->isTicketTransferable($ticket);

        $this->assertFalse($result);
    }

    public function testIsTicketTransferableReturnsFalseForReservedTicket(): void
    {
        $ticket = new Ticket();
        $ticket->setStatus(Ticket::STATUS_RESERVED);

        $event = $this->createMock(Event::class);
        $futureDate = new \DateTimeImmutable('+1 month');
        $event->method('getEventDate')->willReturn($futureDate);
        $ticket->setEvent($event);

        $result = $this->service->isTicketTransferable($ticket);

        $this->assertFalse($result);
    }

    public function testTransferTicketChangesOwner(): void
    {
        $oldUser = $this->createMock(User::class);
        $newUser = $this->createMock(User::class);

        $ticket = new Ticket();
        $ticket->setStatus(Ticket::STATUS_PURCHASED);
        $ticket->setUser($oldUser);

        $event = $this->createMock(Event::class);
        $futureDate = new \DateTimeImmutable('+1 month');
        $event->method('getEventDate')->willReturn($futureDate);
        $ticket->setEvent($event);

        $this->entityManager->expects($this->once())->method('flush');

        $this->service->transferTicket($ticket, $newUser);

        $this->assertSame($newUser, $ticket->getUser());
    }

    public function testTransferTicketThrowsExceptionWhenNotTransferable(): void
    {
        $newUser = $this->createMock(User::class);

        $ticket = new Ticket();
        $ticket->setStatus(Ticket::STATUS_CANCELLED);

        $event = $this->createMock(Event::class);
        $futureDate = new \DateTimeImmutable('+1 month');
        $event->method('getEventDate')->willReturn($futureDate);
        $ticket->setEvent($event);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Ticket cannot be transferred');

        $this->service->transferTicket($ticket, $newUser);
    }
}
