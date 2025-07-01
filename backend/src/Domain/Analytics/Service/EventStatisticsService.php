<?php

namespace App\Domain\Analytics\Service;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Repository\TicketRepository;

final readonly class EventStatisticsService
{
    public function __construct(
        private EventRepository $eventRepository,
        private TicketRepository $ticketRepository
    ) {}

    public function calculateEventStatistics(Event $event): array
    {
        return [
            'basic_stats' => $this->calculateBasicStats($event),
            'revenue_stats' => $this->calculateRevenueStats($event),
            'sales_timeline' => $this->calculateSalesTimeline($event),
            'ticket_type_breakdown' => $this->calculateTicketTypeBreakdown($event),
            'performance_metrics' => $this->calculatePerformanceMetrics($event)
        ];
    }

    public function calculateBasicStats(Event $event): array
    {
        return [
            'total_capacity' => $event->getMaxAttendees(),
            'tickets_sold' => $event->getTicketsSold(),
            'tickets_available' => $event->getAvailableTickets(),
            'occupancy_rate' => $this->calculateOccupancyRate($event),
            'days_until_event' => $this->calculateDaysUntilEvent($event)
        ];
    }

    public function calculateRevenueStats(Event $event): array
    {
        $totalRevenue = $this->ticketRepository->getTotalRevenue($event);
        $averageTicketPrice = $event->getTicketsSold() > 0 
            ? $totalRevenue / $event->getTicketsSold() 
            : 0;

        return [
            'total_revenue' => $totalRevenue,
            'average_ticket_price' => $averageTicketPrice,
            'projected_revenue' => $this->calculateProjectedRevenue($event),
            'revenue_by_type' => $this->ticketRepository->getRevenueByTicketType($event)
        ];
    }

    public function calculateSalesTimeline(Event $event): array
    {
        return $this->ticketRepository->getSalesTimeline($event);
    }

    public function calculateTicketTypeBreakdown(Event $event): array
    {
        $breakdown = [];
        
        foreach ($event->getTicketTypes() as $ticketType) {
            $sold = $this->ticketRepository->count([
                'ticketType' => $ticketType,
                'status' => ['purchased', 'reserved']
            ]);

            $breakdown[] = [
                'type_name' => $ticketType->getName(),
                'total_quantity' => $ticketType->getQuantity(),
                'sold_quantity' => $sold,
                'available_quantity' => $ticketType->getQuantity() - $sold,
                'price' => $ticketType->getPrice(),
                'revenue' => $sold * $ticketType->getPrice(),
                'sell_through_rate' => $ticketType->getQuantity() > 0 
                    ? ($sold / $ticketType->getQuantity()) * 100 
                    : 0
            ];
        }

        return $breakdown;
    }

    public function calculatePerformanceMetrics(Event $event): array
    {
        $conversionRate = $this->calculateConversionRate($event);
        $salesVelocity = $this->calculateSalesVelocity($event);

        return [
            'conversion_rate' => $conversionRate,
            'sales_velocity' => $salesVelocity,
            'time_to_sell_out' => $this->estimateTimeToSellOut($event, $salesVelocity),
            'peak_sales_day' => $this->findPeakSalesDay($event),
            'performance_score' => $this->calculatePerformanceScore($event)
        ];
    }

    private function calculateOccupancyRate(Event $event): float
    {
        return $event->getMaxAttendees() > 0 
            ? ($event->getTicketsSold() / $event->getMaxAttendees()) * 100 
            : 0;
    }

    private function calculateDaysUntilEvent(Event $event): int
    {
        $now = new \DateTimeImmutable();
        $eventDate = $event->getEventDate();
        
        return $now->diff($eventDate)->days;
    }

    private function calculateProjectedRevenue(Event $event): float
    {
        $currentRevenue = $this->ticketRepository->getTotalRevenue($event);
        $occupancyRate = $this->calculateOccupancyRate($event);
        
        if ($occupancyRate > 0) {
            return $currentRevenue / ($occupancyRate / 100);
        }
        
        return $currentRevenue;
    }

    private function calculateConversionRate(Event $event): float
    {
        // This would typically come from analytics service
        $totalViews = 1000; // Placeholder
        $totalSales = $event->getTicketsSold();
        
        return $totalViews > 0 ? ($totalSales / $totalViews) * 100 : 0;
    }

    private function calculateSalesVelocity(Event $event): float
    {
        // Calculate tickets sold per day since event was published
        $publishedAt = $event->getPublishedAt();
        if (!$publishedAt) {
            return 0;
        }

        $daysSincePublished = $publishedAt->diff(new \DateTimeImmutable())->days;
        
        return $daysSincePublished > 0 ? $event->getTicketsSold() / $daysSincePublished : 0;
    }

    private function estimateTimeToSellOut(Event $event, float $salesVelocity): ?int
    {
        if ($salesVelocity <= 0) {
            return null;
        }

        $remainingTickets = $event->getAvailableTickets();
        
        return $remainingTickets > 0 ? (int)ceil($remainingTickets / $salesVelocity) : 0;
    }

    private function findPeakSalesDay(Event $event): ?string
    {
        $salesTimeline = $this->ticketRepository->getSalesTimeline($event);
        
        if (empty($salesTimeline)) {
            return null;
        }

        $maxSales = 0;
        $peakDay = null;

        foreach ($salesTimeline as $day => $sales) {
            if ($sales > $maxSales) {
                $maxSales = $sales;
                $peakDay = $day;
            }
        }

        return $peakDay;
    }

    private function calculatePerformanceScore(Event $event): float
    {
        $occupancyRate = $this->calculateOccupancyRate($event);
        $conversionRate = $this->calculateConversionRate($event);
        $salesVelocity = $this->calculateSalesVelocity($event);

        // Weighted performance score (0-100)
        $score = ($occupancyRate * 0.5) + ($conversionRate * 0.3) + (min($salesVelocity * 10, 20) * 0.2);
        
        return min($score, 100);
    }
}