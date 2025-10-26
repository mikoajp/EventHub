<?php

namespace App\Repository;

use App\Entity\IdempotencyKey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<IdempotencyKey>
 */
class IdempotencyKeyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IdempotencyKey::class);
    }

    public function findByKey(string $idempotencyKey): ?IdempotencyKey
    {
        return $this->findOneBy(['idempotencyKey' => $idempotencyKey]);
    }

    /**
     * Delete old idempotency keys (older than specified days)
     */
    public function deleteOldKeys(int $daysOld = 7): int
    {
        $cutoffDate = new \DateTimeImmutable("-{$daysOld} days");

        return $this->createQueryBuilder('ik')
            ->delete()
            ->where('ik.createdAt < :cutoff')
            ->andWhere('ik.status != :processing')
            ->setParameter('cutoff', $cutoffDate)
            ->setParameter('processing', 'processing')
            ->getQuery()
            ->execute();
    }
}
