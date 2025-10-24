<?php

namespace App\MessageHandler\Event;

use App\Application\Service\EventStatisticsService;
use App\Message\Event\TicketPurchasedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateEventStatisticsHandler
{
    public function __construct(
        private EventStatisticsService $statisticsService
    ) {}

    public function __invoke(TicketPurchasedEvent $event): void
    {
        $this->statisticsService->updateEventStatistics($event->eventId);
        $this->statisticsService->invalidateCache($event->eventId);
    }
}