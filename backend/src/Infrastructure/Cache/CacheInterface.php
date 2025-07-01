<?php

namespace App\Infrastructure\Cache;

interface CacheInterface
{
    /**
     * Get an item from cache
     */
    public function get(string $key, callable $callback, int $ttl = 3600): mixed;

    /**
     * Set an item in cache
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool;

    /**
     * Delete an item from cache
     */
    public function delete(string $key): bool;

    /**
     * Delete multiple items by pattern
     */
    public function deletePattern(string $pattern): bool;

    /**
     * Clear all cache
     */
    public function clear(): bool;

    /**
     * Check if cache is enabled and working
     */
    public function isEnabled(): bool;
}