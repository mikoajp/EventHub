<?php

namespace App\Service;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;

class CacheService
{
    private $cache;
    private $redis;
    private $logger;
    private $isEnabled;
    private $pool;

    public function __construct(
        string $redisUrl = 'redis://localhost:6379',
        bool $cacheEnabled = true,
        ?LoggerInterface $logger = null
    ) {
        $this->isEnabled = $cacheEnabled;
        $this->logger = $logger;

        if ($this->isEnabled) {
            try {
                $this->redis = RedisAdapter::createConnection($redisUrl);
                $this->cache = new RedisAdapter($this->redis);
                $this->pool = new TagAwareAdapter($this->cache);
            } catch (\Exception $e) {
                $this->isEnabled = false;
                $this->logger?->error('Failed to connect to Redis: ' . $e->getMessage());
            }
        }
    }

    /**
     * Get an item from cache
     *
     * @param string $key Cache key
     * @param callable $callback Function to generate value if not in cache
     * @param int $ttl Time to live in seconds
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function get(string $key, callable $callback, int $ttl = 3600, array $tags = []): mixed
    {
        if (!$this->isEnabled) {
            return $callback();
        }

        try {
            return $this->pool->get($key, function (ItemInterface $item) use ($callback, $ttl, $tags) {
                if (!empty($tags) && method_exists($item, 'tag')) {
                    $item->tag($tags);
                }
                $item->expiresAfter($ttl);
                return $callback();
            });
        } catch (\Exception $e) {
            $this->logger?->error('Cache error: ' . $e->getMessage());
            return $callback();
        }
    }

    /**
     * Delete an item from cache
     *
     * @param string $key Cache key
     * @return bool
     * @throws InvalidArgumentException
     */
    public function delete(string $key): bool
    {
        if (!$this->isEnabled) {
            return true;
        }

        try {
            return $this->pool->deleteItem($key);
        } catch (\Exception $e) {
            $this->logger?->error('Cache delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete multiple items by pattern
     *
     * @param string $pattern Pattern to match keys
     * @return bool
     */
    public function deletePattern(string $pattern): bool
    {
        if (!$this->isEnabled || !$this->redis) {
            return true;
        }

        try {
            $deleted = 0;
            if (method_exists($this->redis, 'scan')) {
                $it = null;
                do {
                    $keys = $this->redis->scan($it, $pattern, 1000) ?: [];
                    if (!empty($keys)) {
                        $deleted += $this->redis->del($keys);
                    }
                } while ($it !== 0 && $it !== null);
            }
            return true;
        } catch (\Exception $e) {
            $this->logger?->error('Cache pattern delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear all cache
     *
     * @return bool
     */
    public function clear(): bool
    {
        if (!$this->isEnabled) {
            return true;
        }

        try {
            return $this->pool->clear();
        } catch (\Exception $e) {
            $this->logger?->error('Cache clear error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if cache is enabled and working
     *
     * @return bool
     */
    public function invalidateTags(array $tags): bool
    {
        if (!$this->isEnabled) {
            return true;
        }
        try {
            return $this->pool instanceof TagAwareAdapter ? $this->pool->invalidateTags($tags) : false;
        } catch (\Exception $e) {
            $this->logger?->error('Cache invalidate tags error: ' . $e->getMessage());
            return false;
        }
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

}