<?php

namespace App\Repository;

use App\Entity\RefreshToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Gesdinet\JWTRefreshTokenBundle\Doctrine\RefreshTokenRepositoryInterface;

class RefreshTokenRepository extends ServiceEntityRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    /**
     * @param string $refreshToken
     * @return RefreshToken|null
     */
    public function findOneByRefreshToken(string $refreshToken): ?RefreshToken
    {
        return $this->findOneBy(['refreshToken' => $refreshToken]);
    }

    /**
     * Find all invalid (expired) refresh tokens.
     * @return RefreshToken[]
     */
    public function findInvalid($datetime = null): array
    {
        $cutoff = $datetime instanceof \DateTimeInterface ? \DateTimeImmutable::createFromMutable($datetime) : new \DateTimeImmutable();
        return $this->createQueryBuilder('t')
            ->where('t.valid < :cutoff')
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getResult();
    }
}
