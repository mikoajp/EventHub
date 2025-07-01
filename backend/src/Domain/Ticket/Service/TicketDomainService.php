<?php

namespace App\Domain\Ticket\Service;

use App\Entity\Ticket;
use App\Entity\TicketType;
use App\Entity\User;
use App\Entity\Event;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class TicketDomainService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TicketRepository $ticketRepository
    ) {}

    public function createTicket(User $user, Event $event, TicketType $ticketType): Ticket
    {
        $ticket = new Ticket();
        $ticket->setUser($user)
            ->setEvent($event)
            ->setTicketType($ticketType)
            ->setStatus(Ticket::STATUS_RESERVED)
            ->setPurchaseDate(new \DateTimeImmutable())
            ->setPrice($ticketType->getPrice());

        $this->entityManager->persist($ticket);
        $this->entityManager->flush();

        return $ticket;
    }

    public function confirmTicketPurchase(Ticket $ticket, string $paymentId): void
    {
        $ticket->setStatus(Ticket::STATUS_PURCHASED)
            ->setPaymentId($paymentId)
            ->setPurchaseDate(new \DateTimeImmutable());

        $this->entityManager->flush();
    }

    public function cancelTicket(Ticket $ticket, string $reason = null): void
    {
        $ticket->setStatus(Ticket::STATUS_CANCELLED)
            ->setCancellationReason($reason)
            ->setCancelledAt(new \DateTimeImmutable());

        $this->entityManager->flush();
    }

    public function refundTicket(Ticket $ticket, string $refundId): void
    {
        $ticket->setStatus(Ticket::STATUS_REFUNDED)
            ->setRefundId($refundId)
            ->setRefundedAt(new \DateTimeImmutable());

        $this->entityManager->flush();
    }

    public function isTicketTransferable(Ticket $ticket): bool
    {
        return $ticket->getStatus() === Ticket::STATUS_PURCHASED &&
               $ticket->getEvent()->getEventDate() > new \DateTimeImmutable();
    }

    public function transferTicket(Ticket $ticket, User $newOwner): void
    {
        if (!$this->isTicketTransferable($ticket)) {
            throw new \DomainException('Ticket cannot be transferred');
        }

        $ticket->setUser($newOwner)
            ->setTransferredAt(new \DateTimeImmutable());

        $this->entityManager->flush();
    }
}