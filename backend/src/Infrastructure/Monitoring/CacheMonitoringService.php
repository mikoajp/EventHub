<?php

namespace App\Infrastructure\Monitoring;

use App\Infrastructure\Cache\CacheInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;

final readonly class CacheMonitoringService
{
    public function __construct(
        private CacheInterface $cache,
        private string $redisUrl = 'redis://localhost:6379'
    ) {}

    public function getCacheStats(): array
    {
        if (!$this->cache->isEnabled()) {
            return ['status' => 'disabled'];
        }

        try {
            $redis = RedisAdapter::createConnection($this->redisUrl);
            $info = $redis->info();

            return [
                'status' => 'enabled',
                'connection' => 'active',
                'memory_usage' => $info['used_memory_human'] ?? 'unknown',
                'total_keys' => $info['db0']['keys'] ?? 0,
                'hits' => (int)($info['keyspace_hits'] ?? 0),
                'misses' => (int)($info['keyspace_misses'] ?? 0),
                'hit_rate' => $this->calculateHitRate($info),
                'uptime' => (int)($info['uptime_in_seconds'] ?? 0),
                'connected_clients' => (int)($info['connected_clients'] ?? 0),
                'version' => (string)($info['redis_version'] ?? 'unknown'),
                'memory_details' => $this->getMemoryDetails($redis),
                'performance' => $this->getPerformanceMetrics($redis),
                'top_patterns' => $this->analyzeKeyPatterns($redis),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'connection' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    public function clearCache(): bool
    {
        return $this->cache->clear();
    }

    public function clearPattern(string $pattern): bool
    {
        return $this->cache->deletePattern($pattern);
    }

    private function calculateHitRate(array $info): float
    {
        $hits = (int)($info['keyspace_hits'] ?? 0);
        $misses = (int)($info['keyspace_misses'] ?? 0);
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }

    private function getMemoryDetails($redis): array
    {
        $info = $redis->info('memory');
        
        return [
            'used_memory' => $info['used_memory'] ?? 0,
            'used_memory_human' => $info['used_memory_human'] ?? 'unknown',
            'used_memory_peak' => $info['used_memory_peak'] ?? 0,
            'used_memory_peak_human' => $info['used_memory_peak_human'] ?? 'unknown',
            'memory_fragmentation_ratio' => $info['mem_fragmentation_ratio'] ?? 0
        ];
    }

    private function getPerformanceMetrics($redis): array
    {
        $info = $redis->info('stats');
        
        return [
            'total_commands_processed' => $info['total_commands_processed'] ?? 0,
            'instantaneous_ops_per_sec' => $info['instantaneous_ops_per_sec'] ?? 0,
            'total_net_input_bytes' => $info['total_net_input_bytes'] ?? 0,
            'total_net_output_bytes' => $info['total_net_output_bytes'] ?? 0,
            'rejected_connections' => $info['rejected_connections'] ?? 0
        ];
    }

    private function analyzeKeyPatterns($redis): array
    {
        try {
            $keys = $redis->keys('*');
            $patterns = [];
            
            foreach ($keys as $key) {
                $pattern = preg_replace('/\d+/', '*', $key);
                $patterns[$pattern] = ($patterns[$pattern] ?? 0) + 1;
            }
            
            arsort($patterns);
            
            return array_slice($patterns, 0, 10); // Top 10 patterns
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}