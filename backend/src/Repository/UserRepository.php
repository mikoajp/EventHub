<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Find a user by their UUID
     */
    public function find($id, $lockMode = null, $lockVersion = null): ?User
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
     * Find a user by their email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Find users by their role
     */
    public function findByRole(string $role): array
    {
        $qb = $this->createQueryBuilder('u');
        
        return $qb->where('JSON_CONTAINS(u.roles, :role) = 1')
            ->setParameter('role', json_encode($role))
            ->getQuery()
            ->getResult();
    }

    /**
     * Find organizers (users with ROLE_ORGANIZER)
     */
    public function findOrganizers(): array
    {
        return $this->findByRole('ROLE_ORGANIZER');
    }

    /**
     * Save a user entity
     */
    public function save(User $user, bool $flush = true): void
    {
        $this->getEntityManager()->persist($user);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a user entity
     */
    public function remove(User $user, bool $flush = true): void
    {
        $this->getEntityManager()->remove($user);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}