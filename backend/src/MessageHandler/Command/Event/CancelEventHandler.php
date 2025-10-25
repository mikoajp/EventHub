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
        
        // Use EventApplicationService which delegates to EventDomainService
        $this->eventApplicationService->cancelEvent($event);
    }
}




