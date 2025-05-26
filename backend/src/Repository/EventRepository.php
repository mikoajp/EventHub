<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Event>
 *
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Find an event by its UUID
     */
    public function find($id, $lockMode = null, $lockVersion = null): ?Event
    {
        if ($id instanceof Uuid) {
            return parent::find($id, $lockMode, $lockVersion);
        }
        
        try {
            $uuid = Uuid::fromString($id);
            return parent::find($uuid, $lockMode, $lockVersion);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Find events organized by a specific user
     */
    public function findByOrganizer(User $organizer): array
    {
        return $this->findBy(['organizer' => $organizer], ['startDate' => 'ASC']);
    }

    /**
     * Find upcoming events
     */
    public function findUpcoming(int $limit = 10): array
    {
        $now = new \DateTimeImmutable();
        
        return $this->createQueryBuilder('e')
            ->where('e.startDate > :now')
            ->setParameter('now', $now)
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events by category
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.category = :category')
            ->setParameter('category', $category)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search events by name or description
     */
    public function searchByTerm(string $searchTerm): array
    {
        return $this->createQueryBuilder('e')
            ->where('LOWER(e.name) LIKE LOWER(:searchTerm)')
            ->orWhere('LOWER(e.description) LIKE LOWER(:searchTerm)')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events with available tickets
     */
    public function findWithAvailableTickets(): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.ticketTypes', 't')
            ->where('t.remainingQuantity > 0')
            ->andWhere('e.startDate > :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Save an event entity
     */
    public function save(Event $event, bool $flush = true): void
    {
        $this->getEntityManager()->persist($event);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove an event entity
     */
    public function remove(Event $event, bool $flush = true): void
    {
        $this->getEntityManager()->remove($event);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}