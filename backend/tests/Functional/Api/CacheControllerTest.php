<?php

namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CacheControllerTest extends WebTestCase
{
    public function testCacheStatsRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/admin/cache/stats');
        
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [Response::HTTP_UNAUTHORIZED, Response::HTTP_FORBIDDEN, Response::HTTP_NOT_FOUND]),
            'Cache stats endpoint should be protected'
        );
    }

    public function testCacheClearRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/admin/cache/clear');
        
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [Response::HTTP_UNAUTHORIZED, Response::HTTP_FORBIDDEN, Response::HTTP_NOT_FOUND]),
            'Cache clear endpoint should be protected'
        );
    }
}
