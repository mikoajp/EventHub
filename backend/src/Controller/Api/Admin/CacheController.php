<?php

namespace App\Controller\Api\Admin;

use App\Service\CacheStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Presenter\CachePresenter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for cache management and statistics
 */
#[Route('/api/admin/cache')]
#[IsGranted('ROLE_ADMIN')]
class CacheController extends AbstractController
{
    public function __construct(
        private readonly CacheStatsService $cacheStatsService,
        private readonly CachePresenter $cachePresenter
    ) {}

    /**
     * Get Redis cache statistics
     */
    #[Route('/stats', name: 'admin_cache_stats', methods: ['GET'])]
    public function getStats(): JsonResponse
    {
        $stats = $this->cacheStatsService->getRedisStats();
        
        return $this->json($this->cachePresenter->presentStats($stats));
    }

    /**
     * Clear all Redis cache
     */
    #[Route('/clear', name: 'admin_cache_clear', methods: ['POST'])]
    public function clearCache(): JsonResponse
    {
        $result = $this->cacheStatsService->clearAllCache();
        
        return $this->json($this->cachePresenter->presentResult($result));
    }

    /**
     * Get metrics for specific cache keys
     */
    #[Route('/keys/{pattern}', name: 'admin_cache_keys', methods: ['GET'])]
    public function getKeyMetrics(string $pattern = '*'): JsonResponse
    {
        $metrics = $this->cacheStatsService->getKeyMetrics($pattern);
        
        return $this->json($this->cachePresenter->presentMetrics($metrics));
    }

    /**
     * Get metrics for event cache keys
     */
    #[Route('/keys/events', name: 'admin_cache_events', methods: ['GET'])]
    public function getEventKeyMetrics(): JsonResponse
    {
        return $this->getKeyMetrics('event.*');
    }

    /**
     * Get metrics for user cache keys
     */
    #[Route('/keys/users', name: 'admin_cache_users', methods: ['GET'])]
    public function getUserKeyMetrics(): JsonResponse
    {
        return $this->getKeyMetrics('user.*');
    }

    /**
     * Get metrics for ticket cache keys
     */
    #[Route('/keys/tickets', name: 'admin_cache_tickets', methods: ['GET'])]
    public function getTicketKeyMetrics(): JsonResponse
    {
        return $this->getKeyMetrics('ticket.*');
    }
}
