<?php

namespace App\Service;

use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Service for monitoring Redis cache performance
 */
class CacheStatsService
{
    private $redis;
    private $cacheService;
    private $isEnabled;

    public function __construct(
        CacheService $cacheService,
        string $redisUrl = 'redis://localhost:6379',
        bool $statsEnabled = true
    ) {
        $this->cacheService = $cacheService;
        $this->isEnabled = $statsEnabled;
        
        if ($this->isEnabled) {
            try {
                $this->redis = RedisAdapter::createConnection($redisUrl);
            } catch (\Exception $e) {
                $this->isEnabled = false;
            }
        }
    }

    /**
     * Get comprehensive Redis statistics
     */
    public function getRedisStats(): array
    {
        if (!$this->isEnabled || !$this->redis) {
            return ['enabled' => false];
        }

        try {
            // Get Redis INFO
            $info = $this->redis->info();
            
            // Calculate hit ratio
            $hits = (int)($info['keyspace_hits'] ?? 0);
            $misses = (int)($info['keyspace_misses'] ?? 0);
            $hitRatio = ($hits + $misses > 0) ? round($hits / ($hits + $misses) * 100, 2) : 0;
            
            // Get memory usage
            $usedMemory = (int)($info['used_memory'] ?? 0);
            $usedMemoryHuman = $info['used_memory_human'] ?? '0B';
            $maxMemory = (int)($info['maxmemory'] ?? 0);
            $maxMemoryHuman = $this->formatBytes($maxMemory);
            $memoryUsagePercent = ($maxMemory > 0) ? round(($usedMemory / $maxMemory) * 100, 2) : 0;
            
            // Get database stats
            $dbStats = [];
            foreach ($info as $key => $value) {
                if (strpos($key, 'db') === 0) {
                    $dbStats[$key] = $value;
                }
            }
            
            // Get key counts by pattern
            $keyPatterns = [
                'events' => 'events.*',
                'users' => 'user.*',
                'tickets' => 'ticket.*',
                'sessions' => 'sess:*'
            ];
            
            $keyCounts = [];
            foreach ($keyPatterns as $name => $pattern) {
                $keyCounts[$name] = count($this->redis->keys($pattern));
            }
            
            return [
                'enabled' => true,
                'uptime' => [
                    'seconds' => (int)($info['uptime_in_seconds'] ?? 0),
                    'days' => (int)($info['uptime_in_days'] ?? 0),
                ],
                'performance' => [
                    'connected_clients' => (int)($info['connected_clients'] ?? 0),
                    'total_commands_processed' => (int)($info['total_commands_processed'] ?? 0),
                    'instantaneous_ops_per_sec' => (int)($info['instantaneous_ops_per_sec'] ?? 0),
                    'hit_rate' => [
                        'hits' => $hits,
                        'misses' => $misses,
                        'ratio' => $hitRatio,
                    ],
                ],
                'memory' => [
                    'used' => $usedMemory,
                    'used_human' => $usedMemoryHuman,
                    'max' => $maxMemory,
                    'max_human' => $maxMemoryHuman,
                    'usage_percent' => $memoryUsagePercent,
                    'fragmentation_ratio' => (float)($info['mem_fragmentation_ratio'] ?? 0),
                ],
                'databases' => $dbStats,
                'key_counts' => $keyCounts,
                'total_keys' => (int)($info['keyspace_hits'] ?? 0) + (int)($info['keyspace_misses'] ?? 0),
                'redis_version' => $info['redis_version'] ?? 'unknown',
            ];
        } catch (\Exception $e) {
            return [
                'enabled' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clear all cache and return stats about what was cleared
     */
    public function clearAllCache(): array
    {
        if (!$this->isEnabled || !$this->redis) {
            return ['success' => false, 'reason' => 'Redis not enabled or connected'];
        }

        try {
            // Get key count before flush
            $beforeCount = $this->redis->dbSize();
            
            // Flush all
            $this->redis->flushAll();
            
            return [
                'success' => true,
                'keys_removed' => $beforeCount,
                'timestamp' => (new \DateTime())->format('c')
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get performance metrics for specific cache keys
     */
    public function getKeyMetrics(string $pattern = '*'): array
    {
        if (!$this->isEnabled || !$this->redis) {
            return ['enabled' => false];
        }

        try {
            $keys = $this->redis->keys($pattern);
            $metrics = [];
            
            foreach ($keys as $key) {
                $ttl = $this->redis->ttl($key);
                $type = $this->redis->type($key);
                $size = 0;
                
                // Get size based on type
                if ($type === 'string') {
                    $size = strlen($this->redis->get($key));
                } elseif ($type === 'hash') {
                    $size = $this->redis->hLen($key);
                } elseif ($type === 'list') {
                    $size = $this->redis->lLen($key);
                } elseif ($type === 'set') {
                    $size = $this->redis->sCard($key);
                } elseif ($type === 'zset') {
                    $size = $this->redis->zCard($key);
                }
                
                $metrics[$key] = [
                    'ttl' => $ttl,
                    'type' => $type,
                    'size' => $size,
                    'expires_in' => $ttl > 0 ? $this->formatTimeRemaining($ttl) : 'never',
                ];
            }
            
            return [
                'enabled' => true,
                'pattern' => $pattern,
                'key_count' => count($keys),
                'keys' => $metrics
            ];
        } catch (\Exception $e) {
            return [
                'enabled' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes === 0) {
            return '0B';
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . $units[$pow];
    }

    /**
     * Format seconds to human readable time
     */
    private function formatTimeRemaining(int $seconds): string
    {
        if ($seconds < 60) {
            return "$seconds seconds";
        }
        
        if ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return "$minutes minutes";
        }
        
        if ($seconds < 86400) {
            $hours = floor($seconds / 3600);
            return "$hours hours";
        }
        
        $days = floor($seconds / 86400);
        return "$days days";
    }
}
