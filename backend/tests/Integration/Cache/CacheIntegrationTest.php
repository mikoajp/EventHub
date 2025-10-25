<?php

namespace App\Tests\Integration\Cache;

use App\Infrastructure\Cache\CacheInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CacheIntegrationTest extends KernelTestCase
{
    private CacheInterface $cache;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->cache = $container->get(CacheInterface::class);
    }

    public function testCacheServiceIsRegistered(): void
    {
        $this->assertInstanceOf(CacheInterface::class, $this->cache);
    }

    public function testCacheSetAndGet(): void
    {
        $key = 'test_cache_key_' . uniqid();
        $value = ['data' => 'test_value', 'timestamp' => time()];
        
        $this->cache->set($key, $value, 60);
        $retrieved = $this->cache->get($key);
        
        $this->assertSame($value, $retrieved);
        
        // Cleanup
        $this->cache->delete($key);
    }

    public function testCacheGetWithCallback(): void
    {
        $key = 'test_callback_' . uniqid();
        $called = false;
        
        $result = $this->cache->get(
            $key,
            function() use (&$called) {
                $called = true;
                return ['generated' => true];
            },
            60
        );
        
        $this->assertTrue($called, 'Callback should be called on cache miss');
        $this->assertSame(['generated' => true], $result);
        
        // Second call should not invoke callback
        $called = false;
        $result2 = $this->cache->get(
            $key,
            function() use (&$called) {
                $called = true;
                return ['generated' => true];
            },
            60
        );
        
        $this->assertFalse($called, 'Callback should not be called on cache hit');
        $this->assertSame(['generated' => true], $result2);
        
        // Cleanup
        $this->cache->delete($key);
    }

    public function testCacheDelete(): void
    {
        $key = 'test_delete_' . uniqid();
        
        $this->cache->set($key, 'value', 60);
        $this->assertNotNull($this->cache->get($key));
        
        $this->cache->delete($key);
        $this->assertNull($this->cache->get($key));
    }

    public function testCacheDeletePattern(): void
    {
        $prefix = 'test_pattern_' . uniqid();
        
        $this->cache->set($prefix . '.key1', 'value1', 60);
        $this->cache->set($prefix . '.key2', 'value2', 60);
        $this->cache->set('other_key', 'value3', 60);
        
        $this->cache->deletePattern($prefix . '.*');
        
        $this->assertNull($this->cache->get($prefix . '.key1'));
        $this->assertNull($this->cache->get($prefix . '.key2'));
        $this->assertNotNull($this->cache->get('other_key'));
        
        // Cleanup
        $this->cache->delete('other_key');
    }

    public function testCacheExpiration(): void
    {
        $key = 'test_expire_' . uniqid();
        
        $this->cache->set($key, 'temporary_value', 1);
        $this->assertNotNull($this->cache->get($key));
        
        sleep(2);
        
        $this->assertNull($this->cache->get($key));
    }

    public function testCacheWithComplexData(): void
    {
        $key = 'test_complex_' . uniqid();
        $complexData = [
            'user' => [
                'id' => 123,
                'name' => 'Test User',
                'roles' => ['ROLE_USER', 'ROLE_ADMIN']
            ],
            'events' => [
                ['id' => 1, 'name' => 'Event 1'],
                ['id' => 2, 'name' => 'Event 2']
            ],
            'metadata' => [
                'timestamp' => time(),
                'version' => '1.0.0'
            ]
        ];
        
        $this->cache->set($key, $complexData, 60);
        $retrieved = $this->cache->get($key);
        
        $this->assertSame($complexData, $retrieved);
        
        // Cleanup
        $this->cache->delete($key);
    }
}
