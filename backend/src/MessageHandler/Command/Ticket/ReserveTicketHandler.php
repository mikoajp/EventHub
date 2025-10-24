<?php

declare(strict_types=1);

namespace App\MessageHandler\Command\Ticket;

use App\Message\Command\Ticket\ReserveTicketCommand;
use App\Application\Service\TicketApplicationService;
use App\Repository\EventRepository;
use App\Repository\TicketTypeRepository;
use App\Repository\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ReserveTicketHandler
{
    public function __construct(
        private TicketApplicationService $ticketApplicationService,
        private EventRepository $eventRepository,
        private TicketTypeRepository $ticketTypeRepository,
        private UserRepository $userRepository,
    ) {}

    public function __invoke(ReserveTicketCommand $command): string
    {
        $event = $this->eventRepository->findByUuid($command->eventId) ?? $this->eventRepository->find($command->eventId);
        if (!$event) {
            throw new \InvalidArgumentException('Event not found');
        }
        $ticketType = $this->ticketTypeRepository->find($command->ticketTypeId);
        if (!$ticketType) {
            throw new \InvalidArgumentException('Ticket type not found');
        }
        $user = $this->userRepository->find($command->userId);
        if (!$user) { throw new \InvalidArgumentException('User not found'); }
        $result = $this->ticketApplicationService->purchaseTicket($user, $event, $ticketType);
        return $result['ticket_id'];
    }
}
