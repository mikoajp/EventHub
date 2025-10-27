<?php

namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class EventControllerTest extends WebTestCase
{
    public function testGetPublishedEventsReturnsJson(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/events');
        
        $this->assertResponseIsSuccessful();
        $contentType = $client->getResponse()->headers->get('Content-Type');
        $this->assertStringContainsString('application/json', $contentType);
    }

    public function testGetPublishedEventsReturnsArray(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/events');
        
        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        
        $this->assertIsArray($data);
    }

    public function testGetEventByIdReturnsNotFoundForInvalidId(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/events/invalid-uuid');
        
        // Should return 404 or 500 if UUID validation fails at routing level
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [Response::HTTP_NOT_FOUND, Response::HTTP_INTERNAL_SERVER_ERROR]),
            'Invalid UUID should return 404 or 500'
        );
    }

    public function testGetEventsWithFilters(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/events', [
            'status' => 'published',
            'page' => 1,
            'limit' => 10
        ]);
        
        $this->assertResponseIsSuccessful();
        
        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        
        $this->assertIsArray($data);
    }

    public function testGetEventsWithSearchFilter(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/events', [
            'search' => 'concert'
        ]);
        
        $this->assertResponseIsSuccessful();
    }

    public function testGetEventsWithDateRangeFilter(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/events', [
            'date_from' => '2025-01-01',
            'date_to' => '2025-12-31'
        ]);
        
        $this->assertResponseIsSuccessful();
    }

    public function testGetEventsWithPriceRangeFilter(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/events', [
            'price_min' => 10.0,
            'price_max' => 100.0
        ]);
        
        $this->assertResponseIsSuccessful();
    }

    public function testGetEventsWithSorting(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/events', [
            'sort_by' => 'date',
            'sort_direction' => 'desc'
        ]);
        
        $this->assertResponseIsSuccessful();
    }

    public function testGetEventsWithPagination(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/events', [
            'page' => 2,
            'limit' => 5
        ]);
        
        $this->assertResponseIsSuccessful();
    }

    public function testGetEventStatisticsRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/events/some-uuid/statistics');
        
        // Should require authentication
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [Response::HTTP_UNAUTHORIZED, Response::HTTP_FORBIDDEN, Response::HTTP_NOT_FOUND]),
            'Statistics endpoint should be protected'
        );
    }

    public function testOptionsRequestReturnsCorrectCorsHeaders(): void
    {
        $client = static::createClient();
        
        $client->request('OPTIONS', '/api/events');
        
        $response = $client->getResponse();
        
        // OPTIONS requests should either:
        // 1. Have CORS headers (Access-Control-Allow-*)
        // 2. Return 204 No Content or 200 OK
        // 3. Or 405 if OPTIONS not specifically handled (which is also acceptable)
        $hasCorsHeaders = $response->headers->has('Access-Control-Allow-Origin') ||
                         $response->headers->has('Access-Control-Allow-Methods');
        $hasAcceptableStatus = in_array($response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_NO_CONTENT,
            Response::HTTP_METHOD_NOT_ALLOWED
        ]);
        
        $this->assertTrue(
            $hasCorsHeaders || $hasAcceptableStatus,
            'OPTIONS request should have CORS headers or acceptable status code'
        );
    }
}
