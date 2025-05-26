<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;

class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    public function getEventStatistics(
        Event $event,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->select([
                'COUNT(t.id) as sold_tickets',
                'SUM(t.price) as total_revenue',
                'tt.name as ticket_type_name',
                'COUNT(t.id) as type_count',
                'AVG(t.price) as avg_price'
            ])
            ->join('t.ticketType', 'tt')
            ->where('t.event = :event')
            ->andWhere('t.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', Ticket::STATUS_PURCHASED)
            ->groupBy('tt.id');

        if ($from) {
            $qb->andWhere('t.purchasedAt >= :from')
               ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('t.purchasedAt <= :to')
               ->setParameter('to', $to);
        }

        $results = $qb->getQuery()->getResult();

        $salesByType = [];
        $totalSold = 0;
        $totalRevenue = 0;

        foreach ($results as $result) {
            $salesByType[] = [
                'ticket_type' => $result['ticket_type_name'],
                'count' => (int) $result['type_count'],
                'revenue' => (int) $result['total_revenue'],
                'avg_price' => (float) $result['avg_price']
            ];
            
            $totalSold += (int) $result['type_count'];
            $totalRevenue += (int) $result['total_revenue'];
        }

        return [
            'sold_tickets' => $totalSold,
            'total_revenue' => $totalRevenue,
            'sales_by_type' => $salesByType,
            'sales_timeline' => $this->getSalesTimeline($event, $from, $to)
        ];
    }

    public function getSalesTimeline(
        Event $event,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->select([
                "DATE(t.purchasedAt) as sale_date",
                "COUNT(t.id) as daily_sales",
                "SUM(t.price) as daily_revenue"
            ])
            ->where('t.event = :event')
            ->andWhere('t.status = :status')
            ->andWhere('t.purchasedAt IS NOT NULL')
            ->setParameter('event', $event)
            ->setParameter('status', Ticket::STATUS_PURCHASED)
            ->groupBy('sale_date')
            ->orderBy('sale_date', 'ASC');

        if ($from) {
            $qb->andWhere('t.purchasedAt >= :from')
               ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('t.purchasedAt <= :to')
               ->setParameter('to', $to);
        }

        return $qb->getQuery()->getResult();
    }

    public function getTotalRevenue(Event $event): int
    {
        $result = $this->createQueryBuilder('t')
            ->select('SUM(t.price) as total_revenue')
            ->where('t.event = :event')
            ->andWhere('t.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', Ticket::STATUS_PURCHASED)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?: 0);
    }

    public function getSalesByTicketType(Event $event): array
    {
        return $this->createQueryBuilder('t')
            ->select([
                'tt.name as ticket_type',
                'COUNT(t.id) as count',
                'SUM(t.price) as revenue'
            ])
            ->join('t.ticketType', 'tt')
            ->where('t.event = :event')
            ->andWhere('t.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', Ticket::STATUS_PURCHASED)
            ->groupBy('tt.id')
            ->getQuery()
            ->getResult();
    }

    public function cancelExpiredReservations(\DateTimeImmutable $expiryTime): int
    {
        return $this->createQueryBuilder('t')
            ->update()
            ->set('t.status', ':cancelled_status')
            ->where('t.status = :reserved_status')
            ->andWhere('t.createdAt < :expiry_time')
            ->setParameter('cancelled_status', Ticket::STATUS_CANCELLED)
            ->setParameter('reserved_status', Ticket::STATUS_RESERVED)
            ->setParameter('expiry_time', $expiryTime)
            ->getQuery()
            ->execute();
    }

    public function findUserTicketsForEvent(string $userId, string $eventId): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.user', 'u')
            ->join('t.event', 'e')
            ->where('u.id = :user_id')
            ->andWhere('e.id = :event_id')
            ->andWhere('t.status IN (:valid_statuses)')
            ->setParameter('user_id', $userId)
            ->setParameter('event_id', $eventId)
            ->setParameter('valid_statuses', [Ticket::STATUS_PURCHASED, Ticket::STATUS_RESERVED])
            ->getQuery()
            ->getResult();
    }
}