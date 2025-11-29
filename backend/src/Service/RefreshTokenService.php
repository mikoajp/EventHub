<?php

namespace App\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;

class RefreshTokenService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RefreshTokenRepository $repo,
        private readonly \App\Repository\UserRepository $userRepository
    ) {}

    public function createToken(User $user, ?RefreshToken $previous = null): RefreshToken
    {
        $tokenValue = bin2hex(random_bytes(32));
        $expires = new \DateTimeImmutable('+7 days');
        $entity = new RefreshToken();
        $entity->setRefreshToken($tokenValue);
        $entity->setUsername($user->getEmail());
        $entity->setValid($expires);
        if ($previous) {
            // Invalidate previous
            $previous->setValid(new \DateTimeImmutable('-1 seconds'));
        }
        $this->em->persist($entity);
        $this->em->flush();
        return $entity;
    }

    public function rotate(string $token): ?RefreshToken
    {
        $existing = $this->repo->findOneByRefreshToken($token);
        if (!$existing) { return null; }
        if ($existing->getValid() < new \DateTimeImmutable()) { return null; }
        $userEmail = $existing->getUsername();
        $user = $this->userRepository->findByEmail($userEmail);
        if (!$user) { return null; }
        return $this->createToken($user, $existing);
    }

    public function revoke(string $token): void
    {
        $existing = $this->repo->findOneByRefreshToken($token);
        if ($existing) {
            $existing->setValid(new \DateTimeImmutable('-1 seconds'));
            $this->em->flush();
        }
    }
}
