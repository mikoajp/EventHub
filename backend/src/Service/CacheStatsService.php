<?php

namespace App\Service;

use App\Infrastructure\Cache\CacheInterface;

final class CacheStatsService
{
    public function __construct(private CacheInterface $cache) {}

    public function getRedisStats(): array
    {
        return [
            'enabled' => $this->cache->isEnabled(),
        ];
    }

    public function clearAllCache(): bool
    {
        return $this->cache->clear();
    }

    public function getKeyMetrics(string $pattern = '*'): array
    {
        // For now return pattern info; can be extended to scan keys if needed
        return [
            'pattern' => $pattern,
        ];
    }
}
