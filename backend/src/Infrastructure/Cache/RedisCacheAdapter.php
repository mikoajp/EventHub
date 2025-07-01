<?php

namespace App\Infrastructure\Cache;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;

final class RedisCacheAdapter implements CacheInterface
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
                $this->logger?->error('Failed to connect to Redis: ' . $e->getMessage());
            }
        }
    }

    public function get(string $key, callable $callback, int $ttl = 3600): mixed
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
            $this->logger?->error('Cache error: ' . $e->getMessage());
            return $callback();
        }
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        if (!$this->isEnabled) {
            return true;
        }

        try {
            $item = $this->cache->getItem($key);
            $item->set($value);
            $item->expiresAfter($ttl);
            return $this->cache->save($item);
        } catch (\Exception $e) {
            $this->logger?->error('Cache set error: ' . $e->getMessage());
            return false;
        }
    }

    public function delete(string $key): bool
    {
        if (!$this->isEnabled) {
            return true;
        }

        try {
            return $this->cache->delete($key);
        } catch (\Exception $e) {
            $this->logger?->error('Cache delete error: ' . $e->getMessage());
            return false;
        }
    }

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
            $this->logger?->error('Cache pattern delete error: ' . $e->getMessage());
            return false;
        }
    }

    public function clear(): bool
    {
        if (!$this->isEnabled) {
            return true;
        }

        try {
            return $this->cache->clear();
        } catch (\Exception $e) {
            $this->logger?->error('Cache clear error: ' . $e->getMessage());
            return false;
        }
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }
}