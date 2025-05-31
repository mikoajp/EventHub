<?php

namespace App\Repository;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Find a user by their email
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->useQueryCache(true)
            ->getOneOrNullResult();
    }

    /**
     * Find users by their role
     *
     * @return User[]
     */
    public function findByRole(string $role, ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('JSON_CONTAINS(u.roles, :role) = 1')
            ->setParameter('role', json_encode($role))
            ->orderBy('u.email', 'ASC');

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
     * Persist a user entity
     *
     * @throws Exception
     */
    public function persist(User $user, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            $em->persist($user);
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
     * Remove a user entity
     *
     * @throws Exception
     */
    public function remove(User $user, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            $em->remove($user);
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
     * Get user statistics (events organized, tickets purchased)
     *
     * @param DateTimeImmutable|null $from Start date for filtering (optional)
     * @param DateTimeImmutable|null $to End date for filtering (optional)
     * @return array<string, mixed>
     */
    public function getUserStatistics(
        User               $user,
        ?DateTimeImmutable $from = null,
        ?DateTimeImmutable $to = null
    ): array {
        $eventQb = $this->createQueryBuilder('u')
            ->select('COUNT(e.id) as event_count')
            ->join('u.events', 'e')
            ->where('u.id = :user')
            ->setParameter('user', $user->getId());

        if ($from) {
            $eventQb->andWhere('e.createdAt >= :from')
                ->setParameter('from', $from);
        }
        if ($to) {
            $eventQb->andWhere('e.createdAt <= :to')
                ->setParameter('to', $to);
        }

        $eventCount = (int) $eventQb->getQuery()
            ->useQueryCache(true)
            ->getSingleScalarResult();

        $ticketQb = $this->createQueryBuilder('u')
            ->select([
                'COUNT(t.id) as ticket_count',
                'SUM(t.price) as total_spent'
            ])
            ->join('u.tickets', 't')
            ->where('u.id = :user')
            ->andWhere('t.status = :status')
            ->setParameter('user', $user->getId())
            ->setParameter('status', 'purchased');

        if ($from) {
            $ticketQb->andWhere('t.purchasedAt >= :from')
                ->setParameter('from', $from);
        }
        if ($to) {
            $ticketQb->andWhere('t.purchasedAt <= :to')
                ->setParameter('to', $to);
        }

        $ticketResult = $ticketQb->getQuery()
            ->useQueryCache(true)
            ->getSingleResult();

        return [
            'event_count' => $eventCount,
            'ticket_count' => (int) ($ticketResult['ticket_count'] ?? 0),
            'total_spent' => (float) ($ticketResult['total_spent'] ?? 0)
        ];
    }

    /**
     * Get total tickets purchased by a user
     *
     * @param User $user
     * @return int
     */
    public function getTotalTicketsPurchased(User $user): int
    {
        $result = $this->createQueryBuilder('u')
            ->select('COUNT(t.id) as ticket_count')
            ->join('u.tickets', 't')
            ->where('u.id = :user')
            ->andWhere('t.status = :status')
            ->setParameter('user', $user->getId())
            ->setParameter('status', 'purchased')
            ->getQuery()
            ->useQueryCache(true)
            ->getSingleScalarResult();

        return (int) ($result ?: 0);
    }
}