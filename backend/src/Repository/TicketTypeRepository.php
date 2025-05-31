<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\TicketType;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

class TicketTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketType::class);
    }

    /**
     * Persist a ticket type entity
     *
     * @throws Exception
     */
    public function persist(TicketType $ticketType, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            $em->persist($ticketType);
            if ($flush) {
                $em->flush();
            }
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }

    /**
     * Remove a ticket type entity
     *
     * @param TicketType $ticketType
     * @param bool $flush
     * @throws Exception
     */
    public function remove(TicketType $ticketType, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            $em->remove($ticketType);
            if ($flush) {
                $em->flush();
            }
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }

    /**
     * Get ticket type statistics for an event
     *
     * @param Event $event The event to analyze
     * @param DateTimeImmutable|null $from Start date for filtering (optional)
     * @param DateTimeImmutable|null $to End date for filtering (optional)
     * @return array<string, mixed>
     */
    public function getTicketTypeStatistics(
        Event              $event,
        ?DateTimeImmutable $from = null,
        ?DateTimeImmutable $to = null
    ): array {
        $qb = $this->createQueryBuilder('tt')
            ->select([
                'tt.name as ticket_type_name',
                'tt.price as ticket_price',
                'tt.remainingQuantity as remaining_quantity',
                'tt.quantity as total_quantity',
                '(tt.quantity - tt.remainingQuantity) as sold_quantity'
            ])
            ->where('tt.event = :event')
            ->setParameter('event', $event);

        if ($from) {
            $qb->andWhere('tt.createdAt >= :from')
                ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('tt.createdAt <= :to')
                ->setParameter('to', $to);
        }

        $results = $qb->getQuery()
            ->useQueryCache(true)
            ->getResult();

        $totalTickets = 0;
        $totalSold = 0;
        $totalRemaining = 0;
        $statsByType = [];

        foreach ($results as $result) {
            $statsByType[] = [
                'ticket_type' => $result['ticket_type_name'],
                'price' => (float) $result['ticket_price'],
                'total_quantity' => (int) $result['total_quantity'],
                'sold_quantity' => (int) $result['sold_quantity'],
                'remaining_quantity' => (int) $result['remaining_quantity']
            ];
            $totalTickets += (int) $result['total_quantity'];
            $totalSold += (int) $result['sold_quantity'];
            $totalRemaining += (int) $result['remaining_quantity'];
        }

        return [
            'total_tickets' => $totalTickets,
            'total_sold' => $totalSold,
            'total_remaining' => $totalRemaining,
            'stats_by_type' => $statsByType
        ];
    }

    /**
     * Get total available quantity for an event
     *
     * @param Event $event
     * @return int
     */
    public function getTotalAvailableQuantity(Event $event): int
    {
        $result = $this->createQueryBuilder('tt')
            ->select('SUM(tt.remainingQuantity) as total_remaining')
            ->where('tt.event = :event')
            ->setParameter('event', $event)
            ->getQuery()
            ->useQueryCache(true)
            ->getSingleScalarResult();

        return (int) ($result ?: 0);
    }
}