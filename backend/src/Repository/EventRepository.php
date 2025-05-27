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
     * Get event statistics
     *
     * @param DateTimeImmutable|null $from Start date for filtering (optional)
     * @param DateTimeImmutable|null $to End date for filtering (optional)
     * @return array<string, mixed>
     */
    public function getEventStatistics(
        Event              $event,
        ?DateTimeImmutable $from = null,
        ?DateTimeImmutable $to = null
    ): array {
        $qb = $this->createQueryBuilder('e')
            ->select([
                'COUNT(t.id) as sold_tickets',
                'SUM(t.price) as total_revenue',
                'tt.name as ticket_type_name',
                'COUNT(t.id) as type_count',
                'AVG(t.price) as avg_price'
            ])
            ->join('e.ticketTypes', 'tt')
            ->join('tt.tickets', 't')
            ->where('e.id = :event')
            ->andWhere('t.status = :status')
            ->setParameter('event', $event->getId())
            ->setParameter('status', 'purchased')
            ->groupBy('tt.id');

        if ($from) {
            $qb->andWhere('t.purchasedAt >= :from')
                ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('t.purchasedAt <= :to')
                ->setParameter('to', $to);
        }

        $results = $qb->getQuery()
            ->useQueryCache(true)
            ->getResult();

        $salesByType = [];
        $totalSold = 0;
        $totalRevenue = 0;

        foreach ($results as $result) {
            $salesByType[] = [
                'ticket_type' => $result['ticket_type_name'] ?? '',
                'count' => (int) ($result['type_count'] ?? 0),
                'revenue' => (float) ($result['total_revenue'] ?? 0),
                'avg_price' => (float) ($result['avg_price'] ?? 0)
            ];
            $totalSold += (int) ($result['type_count'] ?? 0);
            $totalRevenue += (float) ($result['total_reset'] ?? 0);
        }

        return [
            'sold_tickets' => $totalSold,
            'total_revenue' => $totalRevenue,
            'sales_by_type' => $salesByType
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
        $result = $this->createQueryBuilder('e')
            ->select('SUM(t.price) as total_revenue')
            ->join('e.ticketTypes', 'tt')
            ->join('tt.tickets', 't')
            ->where('e.id = :event')
            ->andWhere('t.status = :status')
            ->setParameter('event', $event->getId())
            ->setParameter('status', 'purchased')
            ->getQuery()
            ->useQueryCache(true)
            ->getSingleScalarResult();

        return (float) ($result ?: 0);
    }
}