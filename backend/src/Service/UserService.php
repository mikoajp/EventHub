<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\Exception\ORMException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use DateTimeImmutable;

class UserService
{
    private const CACHE_TTL_USER = 3600; // 1 hour
    private const CACHE_TTL_ORGANIZERS = 1800; // 30 minutes
    private const CACHE_TTL_STATISTICS = 900; // 15 minutes
    private const CACHE_KEY_USER_PREFIX = 'user.';
    private const CACHE_KEY_USER_EMAIL_PREFIX = 'user.email.';
    private const CACHE_KEY_ORGANIZERS = 'users.organizers';
    private const CACHE_KEY_USER_STATS_PREFIX = 'user.stats.';

    public function __construct(
        private UserRepository $userRepository,
        private CacheService $cacheService
    ) {}

    /**
     * Find user by UUID with cache
     * @throws InvalidArgumentException
     */
    public function findUserByUuid(string $id): ?User
    {
        $cacheKey = self::CACHE_KEY_USER_PREFIX . $id;
        
        return $this->cacheService->get($cacheKey, function() use ($id) {
            return $this->userRepository->findByUuid($id);
        }, self::CACHE_TTL_USER);
    }

    /**
     * Find user by email with cache
     * @throws InvalidArgumentException
     */
    public function findUserByEmail(string $email): ?User
    {
        $cacheKey = self::CACHE_KEY_USER_EMAIL_PREFIX . md5($email);
        
        return $this->cacheService->get($cacheKey, function() use ($email) {
            return $this->userRepository->findByEmail($email);
        }, self::CACHE_TTL_USER);
    }

    /**
     * Find organizers with cache
     * @throws InvalidArgumentException
     */
    public function findOrganizers(?int $limit = null, ?int $offset = null): array
    {
        $cacheKey = self::CACHE_KEY_ORGANIZERS;
        if ($limit !== null || $offset !== null) {
            $cacheKey .= ".limit_{$limit}.offset_{$offset}";
        }
        
        return $this->cacheService->get($cacheKey, function() use ($limit, $offset) {
            return $this->userRepository->findOrganizers($limit, $offset);
        }, self::CACHE_TTL_ORGANIZERS);
    }

    /**
     * Get user statistics with cache
     * @throws InvalidArgumentException
     */
    public function getUserStatistics(
        User $user,
        ?DateTimeImmutable $from = null,
        ?DateTimeImmutable $to = null
    ): array {
        $fromKey = $from ? $from->format('Ymd') : 'all';
        $toKey = $to ? $to->format('Ymd') : 'all';
        $cacheKey = self::CACHE_KEY_USER_STATS_PREFIX . $user->getId() . ".{$fromKey}_{$toKey}";
        
        return $this->cacheService->get($cacheKey, function() use ($user, $from, $to) {
            return $this->userRepository->getUserStatistics($user, $from, $to);
        }, self::CACHE_TTL_STATISTICS);
    }

    /**
     * Get total tickets purchased with cache
     * @throws InvalidArgumentException
     */
    public function getTotalTicketsPurchased(User $user): int
    {
        $cacheKey = self::CACHE_KEY_USER_STATS_PREFIX . $user->getId() . '.tickets_purchased';
        
        return $this->cacheService->get($cacheKey, function() use ($user) {
            return $this->userRepository->getTotalTicketsPurchased($user);
        }, self::CACHE_TTL_STATISTICS);
    }

    /**
     * Persist a user and invalidate related cache
     * @throws ORMException
     */
    public function saveUser(User $user): void
    {
        $this->userRepository->persist($user);
        $this->invalidateUserCache($user);
    }

    /**
     * Remove a user and invalidate related cache
     * @throws ORMException
     * @throws InvalidArgumentException
     */
    public function removeUser(User $user): void
    {
        $this->userRepository->remove($user);
        $this->invalidateUserCache($user);
    }

    /**
     * Invalidate all cache related to a user
     * @throws InvalidArgumentException
     */
    private function invalidateUserCache(User $user): void
    {
        $this->cacheService->delete(self::CACHE_KEY_USER_PREFIX . $user->getId());

        $this->cacheService->delete(self::CACHE_KEY_USER_EMAIL_PREFIX . md5($user->getEmail()));

        if (in_array('ROLE_ORGANIZER', $user->getRoles())) {
            $this->cacheService->deletePattern(self::CACHE_KEY_ORGANIZERS . '*');
        }

        $this->cacheService->deletePattern(self::CACHE_KEY_USER_STATS_PREFIX . $user->getId() . '*');
    }
}
