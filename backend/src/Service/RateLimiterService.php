<?php

namespace App\Service;

use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\RedisStorage;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\RateLimiter\Policy\TokenBucketLimiter;
use Symfony\Component\RateLimiter\RateLimit;

class RateLimiterService
{
    private RateLimiterFactory $factory;
    private RedisStorage $storage;

    public function __construct(string $redisUrl = 'redis://localhost:6379')
    {
        $redis = RedisAdapter::createConnection($redisUrl);
        $this->storage = new RedisStorage($redis);
        
        $this->factory = new RateLimiterFactory([
            'id' => 'api',
            'policy' => 'token_bucket',
            'limit' => 100,
            'rate' => ['interval' => '15 minutes'],
        ], $this->storage);
    }

    /**
     * Check if a request is allowed based on rate limits
     *
     * @param string $key Identifier for the rate limit (e.g. IP address, user ID)
     * @param int $tokens Number of tokens to consume (default: 1)
     * @return bool Whether the request is allowed
     */
    public function isAllowed(string $key, int $tokens = 1): bool
    {
        $limiter = $this->factory->create($key);
        $limit = $limiter->consume($tokens);
        
        return $limit->isAccepted();
    }

    /**
     * Get detailed rate limit information
     *
     * @param string $key Identifier for the rate limit
     * @return array Rate limit information
     */
    public function getRateLimitInfo(string $key): array
    {
        $limiter = $this->factory->create($key);
        $limit = $limiter->consume(0);
        
        return [
            'limit' => $limit->getLimit(),
            'remaining' => $limit->getRemainingTokens(),
            'reset' => $limit->getRetryAfter()->getTimestamp(),
            'accepted' => $limit->isAccepted()
        ];
    }

    /**
     * Create a custom rate limiter with specific configuration
     *
     * @param string $id Unique identifier for this limiter
     * @param int $limit Maximum number of tokens
     * @param string $interval Time interval (e.g. '1 hour', '30 minutes')
     * @return RateLimiterFactory
     */
    public function createCustomLimiter(string $id, int $limit, string $interval): RateLimiterFactory
    {
        return new RateLimiterFactory([
            'id' => $id,
            'policy' => 'token_bucket',
            'limit' => $limit,
            'rate' => ['interval' => $interval],
        ], $this->storage);
    }
}