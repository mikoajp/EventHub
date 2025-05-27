<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Event>
 */
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

        return null;
    }

    /**
     * Find events organized by a specific user
     *
     * @return Event[]
     */
    public function findByOrganizer(User $organizer, ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.organizer = :organizer')
            ->setParameter('organizer', $organizer)
            ->orderBy('e.startDate', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()
            ->useQueryCache(true)
            ->getResult();
    }

    /**
     * Find upcoming events
     *
     * @return Event[]
     */
    public function findUpcoming(int $limit = 10, ?int $offset = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.startDate > :now')
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults($limit);

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()
            ->useQueryCache(true)
            ->getResult();
    }

    /**
     * Find events by category
     *
     * @return Event[]
     */
    public function findByCategory(string $category, ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.category = :category')
            ->setParameter('category', $category)
            ->orderBy('e.startDate', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()
            ->useQueryCache(true)
            ->getResult();
    }

    /**
     * Search events by name or description
     *
     * @return Event[]
     */
    public function searchByTerm(string $searchTerm, ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('LOWER(e.name) LIKE LOWER(:searchTerm)')
            ->orWhere('LOWER(e.description) LIKE LOWER(:searchTerm)')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('e.startDate', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()
            ->useQueryCache(true)
            ->getResult();
    }

    /**
     * Find events with available tickets
     *
     * @return Event[]
     */
    public function findWithAvailableTickets(?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->join('e.ticketTypes', 'tt')
            ->where('tt.remainingQuantity > 0')
            ->andWhere('e.startDate > :now')
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('e.startDate', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()
            ->useQueryCache(true)
            ->getResult();
    }

    /**
     * Persist an event entity
     *
     * @throws ORMException|Exception
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
     * @throws ORMException
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
     * Get total revenue for an event
     *
     * @param Event $event
     * @return float
     */
    public function getTotalRevenue(Event $event): float
    {
        $result = $this->getEntityManager()->createQueryBuilder()
            ->select('SUM(t.price) as total_revenue')
            ->from('App\Entity\Ticket', 't')
            ->where('t.event = :event')
            ->andWhere('t.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', 'purchased')
            ->getQuery()
            ->useQueryCache(true)
            ->getSingleScalarResult();

        return (float) ($result ?: 0);
    }

    /**
     * Get total tickets sold for an event
     *
     * @param Event $event
     * @return int
     */
    public function getTotalTicketsSold(Event $event): int
    {
        $result = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(t.id) as total_sold')
            ->from('App\Entity\Ticket', 't')
            ->where('t.event = :event')
            ->andWhere('t.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', 'purchased')
            ->getQuery()
            ->useQueryCache(true)
            ->getSingleScalarResult();

        return (int) ($result ?: 0);
    }

    /**
     * Get revenue by ticket type for an event
     *
     * @param Event $event
     * @param DateTimeImmutable|null $from
     * @param DateTimeImmutable|null $to
     * @return array
     */
    public function getRevenueByTicketType(Event $event, ?DateTimeImmutable $from = null, ?DateTimeImmutable $to = null): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('tt.name as ticketTypeName, AVG(t.price) as avgPrice, COUNT(t.id) as soldCount, SUM(t.price) as totalRevenue')
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

        return array_map(function($result) {
            return [
                'ticket_type' => $result['ticketTypeName'],
                'avg_price' => (float)$result['avgPrice'],
                'sold_count' => (int)$result['soldCount'],
                'total_revenue' => (float)$result['totalRevenue'],
                'revenue_formatted' => number_format($result['totalRevenue'] / 100, 2)
            ];
        }, $results);
    }
}