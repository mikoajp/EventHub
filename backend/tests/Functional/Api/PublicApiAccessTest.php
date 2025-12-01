<?php

namespace App\Tests\Functional\Api;

use App\Tests\BaseWebTestCase;

/**
 * Tests public API access without authentication
 */
class PublicApiAccessTest extends BaseWebTestCase
{
    public function testPublicEventsEndpointIsAccessibleWithoutAuth(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/events');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    public function testPublicEventDetailEndpointIsAccessibleWithoutAuth(): void
    {
        $client = static::createClient();
        
        // Even if event doesn't exist, should not get 401 (Unauthorized)
        // Should get 404 (Not Found) instead
        $client->request('GET', '/api/events/999');
        
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertNotEquals(401, $statusCode, 'Public endpoint should not return 401 Unauthorized');
        $this->assertContains($statusCode, [200, 404], 'Should return 200 OK or 404 Not Found, not 401');
    }

    public function testPublicFilterOptionsEndpointIsAccessible(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/events/filters/options');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    public function testPublicTicketAvailabilityEndpointIsAccessible(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/tickets/availability?eventId=1');
        
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertNotEquals(401, $statusCode, 'Public endpoint should not require authentication');
    }

    public function testPublicHealthEndpointIsAccessible(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/health');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    public function testProtectedEndpointsRequireAuthentication(): void
    {
        $client = static::createClient();
        
        // Test POST /api/tickets/purchase without auth
        $client->request('POST', '/api/tickets/purchase', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'ticketTypeId' => 1,
            'quantity' => 1
        ]));
        
        $this->assertResponseStatusCodeSame(401, 'Protected endpoint should require authentication');
    }

    public function testMyTicketsEndpointRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/tickets/my');
        
        $this->assertResponseStatusCodeSame(401, 'Protected endpoint should require authentication');
    }

    public function testCreateEventRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/events', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test Event',
            'description' => 'Test Description'
        ]));
        
        $this->assertResponseStatusCodeSame(401, 'Creating events should require authentication');
    }

    public function testUpdateEventRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('PUT', '/api/events/1', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Updated Event'
        ]));
        
        $this->assertResponseStatusCodeSame(401, 'Updating events should require authentication');
    }

    public function testDeleteEventRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('DELETE', '/api/events/1');
        
        $this->assertResponseStatusCodeSame(401, 'Deleting events should require authentication');
    }

    public function testPublicAssetsAreAccessible(): void
    {
        $client = static::createClient();
        
        // Test that asset paths don't require auth (should get 404, not 401)
        $client->request('GET', '/bundles/test.js');
        
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertNotEquals(401, $statusCode, 'Public assets should not require authentication');
    }
}
