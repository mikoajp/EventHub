<?php

namespace App\MessageHandler\Query\Event;

use App\Message\Query\Event\GetEventStatisticsQuery;
use App\Repository\EventRepository;
use App\Repository\TicketRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class GetEventStatisticsHandler
{
    public function __construct(
        private EventRepository $eventRepository,
        private TicketRepository $ticketRepository
    ) {}

    public function __invoke(GetEventStatisticsQuery $query): array
    {
        $event = $this->eventRepository->find(Uuid::fromString($query->eventId));
        
        if (!$event) {
            throw new \InvalidArgumentException('Event not found');
        }

        $statistics = $this->ticketRepository->getEventStatistics(
            $event,
            $query->from,
            $query->to
        );

        return [
            'event_id' => $query->eventId,
            'total_tickets' => $event->getMaxTickets(),
            'sold_tickets' => $statistics['sold_tickets'],
            'available_tickets' => $event->getAvailableTickets(),
            'total_revenue' => $statistics['total_revenue'],
            'sales_by_type' => $statistics['sales_by_type'],
            'sales_timeline' => $statistics['sales_timeline'],
            'period' => [
                'from' => $query->from?->format('Y-m-d H:i:s'),
                'to' => $query->to?->format('Y-m-d H:i:s')
            ]
        ];
    }
}