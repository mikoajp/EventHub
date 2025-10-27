<?php

namespace App\Tests\Integration\Lock;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\RedisStore;

final class RedisLockTest extends KernelTestCase
{
    private function getRedis(): \Redis
    {
        if (!class_exists(\Redis::class)) {
            $this->markTestSkipped('php-redis extension not installed');
        }
        $redis = new \Redis();
        $url = getenv('REDIS_URL') ?: '127.0.0.1:6379';
        [$host, $port] = array_pad(explode(':', str_replace('redis://', '', $url)), 2, 6379);
        try {
            $redis->connect($host, (int)$port);
        } catch (\RedisException $e) {
            $this->markTestSkipped('Redis not available at ' . $host . ':' . $port);
        }
        return $redis;
    }

    public function testRedisLockPreventsConcurrentAcquisition(): void
    {
        $redis = $this->getRedis();
        $store = new RedisStore($redis);
        $factory = new LockFactory($store);
        
        // Use a unique resource name for this test to avoid conflicts
        $resourceName = 'test:lock:resource:' . uniqid();

        $lock1 = $factory->createLock($resourceName, 5.0, false);
        $this->assertTrue($lock1->acquire());

        $lock2 = $factory->createLock($resourceName, 5.0, false);
        $this->assertFalse($lock2->acquire(false), 'Second lock should not acquire immediately');

        $lock1->release();
        $this->assertTrue($lock2->acquire(false), 'Second lock can acquire after release');
        $lock2->release();
    }
}
