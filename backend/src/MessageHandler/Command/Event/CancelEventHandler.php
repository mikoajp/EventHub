<?php

declare(strict_types=1);

namespace App\MessageHandler\Command\Event;

use App\Message\Command\Event\CancelEventCommand;
use App\Repository\EventRepository;
use App\Application\Service\EventApplicationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CancelEventHandler
{
    public function __construct(
        private EventRepository $eventRepository,
        private EventApplicationService $eventApplicationService,
    ) {}

    public function __invoke(CancelEventCommand $command): void
    {
         $event = $this->eventRepository->findByUuid($command->eventId) ?? $this->eventRepository->find($command->eventId);
        if (!$event) {
            throw new \InvalidArgumentException('Event not found');
        }
        // set status and cancelledAt via domain rules kept simple here
        if (!$event->canBeCancelled()) {
            throw new \DomainException('Event cannot be cancelled');
        }
        $event->setPreviousStatus($event->getStatus());
        $event->setStatus(\App\Entity\Event::STATUS_CANCELLED);
        $event->setCancelledAt(new \DateTimeImmutable());
        $this->eventRepository->persist($event);
    }
}




