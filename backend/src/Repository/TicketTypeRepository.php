<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\TicketType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<TicketType>
 *
 * @method TicketType|null find($id, $lockMode = null, $lockVersion = null)
 * @method TicketType|null findOneBy(array $criteria, array $orderBy = null)
 * @method TicketType[]    findAll()
 * @method TicketType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TicketTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketType::class);
    }

    /**
     * Find a ticket type by its UUID
     */
    public function find($id, $lockMode = null, $lockVersion = null): ?TicketType
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
     * Find all ticket types for a specific event
     */
    public function findByEvent(Event $event): array
    {
        return $this->findBy(['event' => $event], ['price' => 'ASC']);
    }

    /**
     * Find available ticket types for an event (with remaining quantity > 0)
     */
    public function findAvailableByEvent(Event $event): array
    {
        return $this->createQueryBuilder('tt')
            ->where('tt.event = :event')
            ->andWhere('tt.remainingQuantity > 0')
            ->setParameter('event', $event)
            ->orderBy('tt.price', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if a ticket type has available tickets
     */
    public function hasAvailableTickets(TicketType $ticketType): bool
    {
        return $ticketType->getRemainingQuantity() > 0;
    }

    /**
     * Decrease the remaining quantity of a ticket type
     */
    public function decreaseRemainingQuantity(TicketType $ticketType, int $quantity = 1): void
    {
        $ticketType->setRemainingQuantity(
            max(0, $ticketType->getRemainingQuantity() - $quantity)
        );
        
        $this->save($ticketType);
    }

    /**
     * Save a ticket type entity
     */
    public function save(TicketType $ticketType, bool $flush = true): void
    {
        $this->getEntityManager()->persist($ticketType);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a ticket type entity
     */
    public function remove(TicketType $ticketType, bool $flush = true): void
    {
        $this->getEntityManager()->remove($ticketType);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}