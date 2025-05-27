<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\TicketType;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<TicketType>
 */
class TicketTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketType::class);
    }

    /**
     * Find a ticket type by its UUID (string or Uuid object)
     *
     * @param Uuid|string $id The UUID of the ticket type
     * @param null $lockMode
     * @param null $lockVersion
     * @return TicketType|null
     */
    public function findByUuid($id, $lockMode = null, $lockVersion = null): ?TicketType
    {
        if ($id instanceof Uuid) {
            return parent::find($id, $lockMode, $lockVersion);
        }

        if (is_string($id)) {
            try {
                $uuid = Uuid::fromString($id);
                return parent::find($uuid, $lockMode, $lockVersion);
            } catch (InvalidArgumentException $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Find all ticket types for a specific event, ordered by price ascending
     *
     * @return TicketType[]
     */
    public function findByEvent(Event $event): array
    {
        return $this->createQueryBuilder('tt')
            ->where('tt.event = :event')
            ->setParameter('event', $event)
            ->orderBy('tt.price', 'ASC')
            ->getQuery()
            ->useQueryCache(true)
            ->getResult();
    }

    /**
     * Find available ticket types for an event (with remaining quantity > 0)
     *
     * @return TicketType[]
     */
    public function findAvailableByEvent(Event $event, ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->createQueryBuilder('tt')
            ->where('tt.event = :event')
            ->andWhere('tt.remainingQuantity > 0')
            ->setParameter('event', $event)
            ->orderBy('tt.price', 'ASC');

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
     * Check if a ticket type has available tickets
     *
     * @param TicketType $ticketType
     * @return bool
     */
    public function isAvailable(TicketType $ticketType): bool
    {
        return $ticketType->getRemainingQuantity() > 0;
    }

    /**
     * Decrease the remaining quantity of a ticket type
     *
     * @throws InvalidArgumentException|Exception If the entity is not managed
     */
    public function decreaseRemainingQuantity(TicketType $ticketType, int $quantity = 1): void
    {
        if (!$this->getEntityManager()->contains($ticketType)) {
            throw new InvalidArgumentException('TicketType entity is not managed.');
        }

        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            $ticketType->setRemainingQuantity(
                max(0, $ticketType->getRemainingQuantity() - $quantity)
            );
            $em->persist($ticketType);
            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
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