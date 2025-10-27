<?php

namespace App\Repository;

use App\Entity\Event;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Uid\Uuid;

class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Find an event by its UUID (string or Uuid object)
     *
     * @param Uuid|string $id The UUID of the event
     * @param null $lockMode
     * @param null $lockVersion
     * @return Event|null
     */
    public function findByUuid($id, $lockMode = null, $lockVersion = null): ?Event
    {
        if ($id instanceof Uuid) {
            return parent::find($id, $lockMode, $lockVersion);
        }

        if (is_string($id)) {
            try {
                $uuid = Uuid::fromString($id);
                return parent::find($uuid, $lockMode, $lockVersion);
            } catch (\InvalidArgumentException) {
                return null;
            }
        }
    }

    /**
     * Persist an event entity
     *
     * @throws Exception
     */
    public function persist(Event $event, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            $em->persist($event);
            if ($flush) {
                $em->flush();
            }
            $em->commit();
        } catch (Exception $e) {
            $em->rollback();
            throw $e;
        }
    }

    /**
     * Remove an event entity
     *
     * @throws Exception
     */
    public function remove(Event $event, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            $em->remove($event);
            if ($flush) {
                $em->flush();
            }
            $em->commit();
        } catch (Exception $e) {
            $em->rollback();
            throw $e;
        }
    }

    /**
     * Get ticket sales statistics for an event
     *
     * @param Event $event
     * @param DateTimeImmutable|null $from
     * @param DateTimeImmutable|null $to
     * @return array
     */
    public function getTicketSalesStatistics(Event $event, ?DateTimeImmutable $from = null, ?DateTimeImmutable $to = null): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(t.id) as total, tt.name as ticketTypeName, tt.id as ticketTypeId')
            ->from('App\Entity\Ticket', 't')
            ->join('t.ticketType', 'tt')
            ->where('t.event = :event')
            ->andWhere('t.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', 'purchased');

        if ($from) {
            $qb->andWhere('t.purchasedAt >= :from')
                ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('t.purchasedAt <= :to')
                ->setParameter('to', $to);
        }

        $results = $qb->groupBy('tt.id')
            ->getQuery()
            ->useQueryCache(true)
            ->getResult();

        $byType = [];
        $total = 0;

        foreach ($results as $result) {
            $byType[] = [
                'ticketTypeId' => $result['ticketTypeId'],
                'name' => $result['ticketTypeName'],
                'sold' => (int)$result['total']
            ];
            $total += (int)$result['total'];
        }

        return [
            'total' => $total,
            'byType' => $byType
        ];
    }

    /**
     * Get revenue statistics for an event
     *
     * @param Event $event
     * @param DateTimeImmutable|null $from
     * @param DateTimeImmutable|null $to
     * @return array
     */
    public function getRevenueStatistics(Event $event, ?DateTimeImmutable $from = null, ?DateTimeImmutable $to = null): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('SUM(t.price) as totalRevenue, COUNT(t.id) as ticketCount')
            ->from('App\Entity\Ticket', 't')
            ->where('t.event = :event')
            ->andWhere('t.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', 'purchased');

        if ($from) {
            $qb->andWhere('t.purchasedAt >= :from')
                ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('t.purchasedAt <= :to')
                ->setParameter('to', $to);
        }

        $result = $qb->getQuery()
            ->useQueryCache(true)
            ->getSingleResult();

        return [
            'total' => (float)($result['totalRevenue'] ?? 0),
            'totalFormatted' => number_format(($result['totalRevenue'] ?? 0) / 100, 2),
            'ticketCount' => (int)($result['ticketCount'] ?? 0)
        ];
    }

    /**
     * Get order statistics for an event (simulated from tickets)
     *
     * @param Event $event
     * @param DateTimeImmutable|null $from
     * @param DateTimeImmutable|null $to
     * @return array
     */
    public function getOrderStatistics(Event $event, ?DateTimeImmutable $from = null, ?DateTimeImmutable $to = null): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(t.id) as totalTickets, AVG(t.price) as avgTicketPrice')
            ->from('App\Entity\Ticket', 't')
            ->where('t.event = :event')
            ->andWhere('t.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', 'purchased');

        if ($from) {
            $qb->andWhere('t.purchasedAt >= :from')
                ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('t.purchasedAt <= :to')
                ->setParameter('to', $to);
        }

        $result = $qb->getQuery()
            ->useQueryCache(true)
            ->getSingleResult();

        return [
            'totalOrders' => (int)($result['totalTickets'] ?? 0),
            'avgOrderValue' => (float)($result['avgTicketPrice'] ?? 0),
            'avgOrderValueFormatted' => number_format(($result['avgTicketPrice'] ?? 0) / 100, 2)
        ];
    }

    /**
     * Get daily statistics for an event
     *
     * @param Event $event
     * @param DateTimeImmutable|null $from
     * @param DateTimeImmutable|null $to
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getDailyStatistics(Event $event, ?DateTimeImmutable $from = null, ?DateTimeImmutable $to = null): array
    {
        $sql = "
            SELECT DATE(t.purchased_at) as date, COUNT(t.id) as tickets, SUM(t.price) as revenue
            FROM ticket t
            WHERE t.event_id = :event_id 
            AND t.status = :status
            AND t.purchased_at IS NOT NULL
        ";

        $params = [
            'event_id' => $event->getId()->toString(),
            'status' => 'purchased'
        ];

        if ($from) {
            $sql .= " AND t.purchased_at >= :from";
            $params['from'] = $from->format('Y-m-d H:i:s');
        }

        if ($to) {
            $sql .= " AND t.purchased_at <= :to";
            $params['to'] = $to->format('Y-m-d H:i:s');
        }

        $sql .= " GROUP BY DATE(t.purchased_at) ORDER BY DATE(t.purchased_at) ASC";

        $connection = $this->getEntityManager()->getConnection();
        $result = $connection->executeQuery($sql, $params);
        $results = $result->fetchAllAssociative();

        return array_map(function($result) {
            return [
                'date' => $result['date'],
                'orders' => (int)$result['tickets'],
                'revenue' => (float)$result['revenue'],
                'revenueFormatted' => number_format($result['revenue'] / 100, 2)
            ];
        }, $results);
    }

    /**
     * Get comprehensive event statistics
     *
     * @param Event $event
     * @param DateTimeImmutable|null $from Start date for filtering (optional)
     * @param DateTimeImmutable|null $to End date for filtering (optional)
     * @return array<string, mixed>
     * @throws \Doctrine\DBAL\Exception
     */
    public function getEventStatistics(
        Event              $event,
        ?DateTimeImmutable $from = null,
        ?DateTimeImmutable $to = null
    ): array {
        $ticketSalesData = $this->getTicketSalesStatistics($event, $from, $to);
        $revenueData = $this->getRevenueStatistics($event, $from, $to);
        $orderStats = $this->getOrderStatistics($event, $from, $to);
        $dailyStats = $this->getDailyStatistics($event, $from, $to);

        return [
            'sold_tickets' => $ticketSalesData['total'],
            'total_revenue' => $revenueData['total'],
            'total_orders' => $orderStats['totalOrders'],
            'average_order_value' => $orderStats['avgOrderValue'],
            'sales_by_type' => $ticketSalesData['byType'],
            'daily_breakdown' => $dailyStats,
            'revenue_data' => $revenueData,
            'order_data' => $orderStats
        ];
    }

    /**
     * Find published events
     *
     * @return Event[]
     */
    public function findPublishedEvents(): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.organizer', 'o')->addSelect('PARTIAL o.{id,email}')
            ->leftJoin('e.ticketTypes', 'tt')->addSelect('tt')
            ->where('e.status = :status')
            ->setParameter('status', Event::STATUS_PUBLISHED)
            ->orderBy('e.eventDate', 'ASC')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events with advanced filters
     *
     * @param array $filters
     * @param array $sorting
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function findEventsWithFilters(array $filters = [], array $sorting = [], int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.organizer', 'o')->addSelect('PARTIAL o.{id,email}')
            ->leftJoin('e.ticketTypes', 'tt')->addSelect('tt');

        // Apply filters
        if (!empty($filters['search'])) {
            $qb->andWhere('e.name LIKE :search OR e.description LIKE :search OR e.venue LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $qb->andWhere('e.status IN (:statuses)')
                    ->setParameter('statuses', $filters['status']);
            } else {
                $qb->andWhere('e.status = :status')
                    ->setParameter('status', $filters['status']);
            }
        }

        if (!empty($filters['venue'])) {
            if (is_array($filters['venue'])) {
                $qb->andWhere('e.venue IN (:venues)')
                    ->setParameter('venues', $filters['venue']);
            } else {
                $qb->andWhere('e.venue LIKE :venue')
                    ->setParameter('venue', '%' . $filters['venue'] . '%');
            }
        }

        if (!empty($filters['organizer_id'])) {
            $qb->andWhere('e.organizer = :organizer')
                ->setParameter('organizer', $filters['organizer_id']);
        }

        if (!empty($filters['date_from'])) {
            $qb->andWhere('e.eventDate >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($filters['date_from']));
        }

        if (!empty($filters['date_to'])) {
            $qb->andWhere('e.eventDate <= :dateTo')
                ->setParameter('dateTo', new \DateTime($filters['date_to']));
        }

        if (!empty($filters['price_min'])) {
            $qb->andWhere('tt.price >= :priceMin')
                ->setParameter('priceMin', $filters['price_min'] * 100); // Convert to cents
        }

        if (!empty($filters['price_max'])) {
            $qb->andWhere('tt.price <= :priceMax')
                ->setParameter('priceMax', $filters['price_max'] * 100); // Convert to cents
        }

        if (isset($filters['has_available_tickets']) && $filters['has_available_tickets']) {
            $qb->andWhere('e.maxTickets > (
                SELECT COUNT(t.id) 
                FROM App\Entity\Ticket t 
                WHERE t.event = e AND t.status IN (:purchasedStatuses)
            )')
                ->setParameter('purchasedStatuses', ['purchased', 'reserved']);
        }

        // Apply sorting
        $sortField = $sorting['field'] ?? 'eventDate';
        $sortDirection = strtoupper($sorting['direction'] ?? 'ASC');

        switch ($sortField) {
            case 'name':
                $qb->orderBy('e.name', $sortDirection);
                break;
            case 'venue':
                $qb->orderBy('e.venue', $sortDirection);
                break;
            case 'created_at':
                $qb->orderBy('e.createdAt', $sortDirection);
                break;
            case 'price':
                $qb->orderBy('MIN(tt.price)', $sortDirection)
                    ->groupBy('e.id');
                break;
            case 'popularity':
                $qb->orderBy('(
                    SELECT COUNT(t2.id) 
                    FROM App\Entity\Ticket t2 
                    WHERE t2.event = e AND t2.status = \'purchased\'
                )', $sortDirection);
                break;
            default:
                $qb->orderBy('e.eventDate', $sortDirection);
        }

        // Add secondary sorting
        if ($sortField !== 'eventDate') {
            $qb->addOrderBy('e.eventDate', 'ASC');
        }

        // Calculate total count
        $countQb = clone $qb;
        $countQb->select('COUNT(DISTINCT e.id)');
        $totalCount = $countQb->getQuery()->getSingleScalarResult();

        // Apply pagination
        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)
            ->setMaxResults($limit);

        // Get distinct events (in case of joins)
        $qb->distinct();

        $events = $qb->getQuery()->getResult();

        return [
            'events' => $events,
            'total' => (int)$totalCount,
            'page' => $page,
            'limit' => $limit,
            'pages' => (int)ceil($totalCount / $limit)
        ];
    }

    /**
     * Get unique venues for filter options
     *
     * @return array
     */
    public function getUniqueVenues(): array
    {
        $result = $this->createQueryBuilder('e')
            ->select('DISTINCT e.venue')
            ->orderBy('e.venue', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'venue');
    }

    /**
     * Get price range for events
     *
     * @return array
     */
    public function getPriceRange(): array
    {
        $result = $this->getEntityManager()->createQueryBuilder()
            ->select('MIN(tt.price) as min_price, MAX(tt.price) as max_price')
            ->from('App\Entity\TicketType', 'tt')
            ->join('tt.event', 'e')
            ->where('e.status = :status')
            ->setParameter('status', Event::STATUS_PUBLISHED)
            ->getQuery()
            ->getSingleResult();

        return [
            'min' => (float)($result['min_price'] ?? 0) / 100,
            'max' => (float)($result['max_price'] ?? 0) / 100
        ];
    }

}