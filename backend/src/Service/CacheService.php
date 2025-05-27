<?php

namespace App\Service;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;

class CacheService
{
    private $cache;
    private $redis;
    private $logger;
    private $isEnabled;

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
            } catch (\Exception $e) {
                $this->isEnabled = false;
                if ($this->logger) {
                    $this->logger->error('Failed to connect to Redis: ' . $e->getMessage());
                }
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
    public function get(string $key, callable $callback, int $ttl = 3600)
    {
        if (!$this->isEnabled) {
            return $callback();
        }

        try {
            return $this->cache->get($key, function (ItemInterface $item) use ($callback, $ttl) {
                $item->expiresAfter($ttl);
                return $callback();
            });
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Cache error: ' . $e->getMessage());
            }
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
            return $this->cache->delete($key);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Cache delete error: ' . $e->getMessage());
            }
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
            $keys = $this->redis->keys($pattern);

            if (!empty($keys)) {
                $this->redis->del($keys);
            }

            return true;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Cache pattern delete error: ' . $e->getMessage());
            }
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
            return $this->cache->clear();
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Cache clear error: ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Check if cache is enabled and working
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

}