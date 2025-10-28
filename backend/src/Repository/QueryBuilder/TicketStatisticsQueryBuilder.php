<?php

namespace App\Repository\QueryBuilder;

use App\Entity\Event;
use App\Entity\Ticket;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Builder for ticket statistics queries
 * Encapsulates common query building logic for ticket-based statistics
 */
final class TicketStatisticsQueryBuilder
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {}

    /**
     * Create base query builder for purchased tickets
     */
    public function createBaseQuery(): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->from(Ticket::class, 't')
            ->where('t.status = :status')
            ->setParameter('status', 'purchased');
    }

    /**
     * Add event filter to query
     */
    public function withEvent(QueryBuilder $qb, Event $event): self
    {
        $qb->andWhere('t.event = :event')
            ->setParameter('event', $event);

        return $this;
    }

    /**
     * Add date range filter to query
     */
    public function withDateRange(
        QueryBuilder $qb,
        ?DateTimeImmutable $from = null,
        ?DateTimeImmutable $to = null
    ): self {
        if ($from) {
            $qb->andWhere('t.purchasedAt >= :from')
                ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('t.purchasedAt <= :to')
                ->setParameter('to', $to);
        }

        return $this;
    }

    /**
     * Enable query caching
     */
    public function withCache(QueryBuilder $qb, bool $useCache = true): self
    {
        if ($useCache) {
            $qb->getQuery()->useQueryCache(true);
        }

        return $this;
    }
}
