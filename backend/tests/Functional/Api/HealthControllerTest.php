<?php

namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class HealthControllerTest extends WebTestCase
{
    public function testHealthEndpointReturnsOk(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/health');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testHealthEndpointReturnsJson(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/health');
        
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
    }

    public function testHealthEndpointReturnsCorrectStructure(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/health');
        
        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        
        $this->assertIsArray($data);
        $this->assertArrayHasKey('status', $data);
        $this->assertSame('ok', $data['status']);
    }

    public function testHealthEndpointOnlyAcceptsGetMethod(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/health');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testHealthEndpointIsPubliclyAccessible(): void
    {
        $client = static::createClient();
        
        // Request without authentication
        $client->request('GET', '/health');
        
        // Should not return 401 or 403
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertNotSame(Response::HTTP_UNAUTHORIZED, $statusCode);
        $this->assertNotSame(Response::HTTP_FORBIDDEN, $statusCode);
        $this->assertSame(Response::HTTP_OK, $statusCode);
    }
}
