<?php

namespace App\Infrastructure\Cache;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;

final class RedisCacheAdapter implements CacheInterface
{
    /**
     * Invalidate all items matching any of the provided tags.
     */
    public function invalidateTags(array $tags): bool
    {
        if (!$this->isEnabled) {
            return true;
        }
        try {
            if ($this->pool instanceof TagAwareAdapter) {
                return $this->pool->invalidateTags($tags);
            }
            return false;
        } catch (\Exception $e) {
            $this->logger?->error('Cache invalidate tags error: ' . $e->getMessage());
            return false;
        }
    }

    /** @var \Symfony\Component\Cache\Adapter\RedisAdapter|null */
    private $cache;
    /** @var \Redis|\Predis\ClientInterface|null */
    private $redis;
    /** @var \Psr\Log\LoggerInterface|null */
    private $logger;
    /** @var bool */
    private $isEnabled;
    /** @var \Symfony\Component\Cache\Adapter\TagAwareAdapter|null */
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
                if ($redisUrl === 'array://') {
                    $this->cache = new ArrayAdapter();
                } else {
                    $this->redis = RedisAdapter::createConnection($redisUrl);
                    $this->cache = new RedisAdapter($this->redis);
                }
                $this->pool = new TagAwareAdapter($this->cache);
            } catch (\Exception $e) {
                $this->isEnabled = false;
                $this->logger?->error('Failed to connect to Redis: ' . $e->getMessage());
            }
        }
    }

    public function get(string $key, ?callable $callback = null, int $ttl = 3600): mixed
    {
        if (!$this->isEnabled) {
            return $callback ? $callback() : null;
        }

        try {
            if ($callback) {
                return $this->pool->get($key, function (ItemInterface $item) use ($callback, $ttl) {
                    $item->expiresAfter($ttl);
                    return $callback();
                });
            }
            $item = $this->pool->getItem($key);
            return $item->isHit() ? $item->get() : null;
        } catch (\Exception $e) {
            $this->logger?->error('Cache error: ' . $e->getMessage());
            return $callback ? $callback() : null;
        }
    }

    public function set(string $key, mixed $value, int $ttl = 3600, array $tags = []): bool
    {
        if (!$this->isEnabled) {
            return true;
        }

        try {
            $item = $this->pool->getItem($key);
            $item->set($value);
            if (!empty($tags) && method_exists($item, 'tag')) {
                $item->tag($tags); // requires TagAwareAdapter pool
            }
            $item->expiresAfter($ttl);
            return $this->pool->save($item);
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
            return $this->pool->deleteItem($key);
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
            $deleted = 0;
            if (method_exists($this->redis, 'scan')) {
                $it = null;
                do {
                    $keys = $this->redis->scan($it, $pattern, 1000) ?: [];
                    if (!empty($keys)) {
                        $deleted += $this->redis->del($keys);
                    }
                } while ($it !== 0);
            } elseif (class_exists('Predis\\Collection\\Iterator\\Keyspace') && $this->redis instanceof \Predis\ClientInterface) {
                foreach (new \Predis\Collection\Iterator\Keyspace($this->redis, $pattern, 1000) as $key) {
                    $this->redis->del([$key]);
                    $deleted++;
                }
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
            return $this->pool->clear();
        } catch (\Exception $e) {
            $this->logger?->error('Cache clear error: ' . $e->getMessage());
            return false;
        }
    }

    public function has(string $key): bool
    {
        if (!$this->isEnabled) {
            return false;
        }
        try {
            return $this->pool->hasItem($key);
        } catch (\Exception $e) {
            $this->logger?->error('Cache has error: ' . $e->getMessage());
            return false;
        }
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }
}