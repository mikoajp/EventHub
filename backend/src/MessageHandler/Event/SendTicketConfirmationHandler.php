<?php

namespace App\MessageHandler\Event;

use App\Message\Event\TicketPurchasedEvent;
use App\Repository\TicketRepository;
use App\Service\EmailService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class SendTicketConfirmationHandler
{
    public function __construct(
        private TicketRepository $ticketRepository,
        private EmailService $emailService
    ) {}

    public function __invoke(TicketPurchasedEvent $event): void
    {
        $ticket = $this->ticketRepository->find(Uuid::fromString($event->ticketId));
        
        if (!$ticket) {
            return; // Ticket not found, skip
        }

        $this->emailService->sendTicketConfirmation($ticket);
    }
}