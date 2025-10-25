<?php

namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class TicketControllerTest extends WebTestCase
{
    public function testCheckAvailabilityRequiresValidEventId(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/tickets/availability/invalid-uuid');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPurchaseTicketRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/tickets/purchase', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'event_id' => 'event-uuid',
            'ticket_type_id' => 'type-uuid',
            'payment_method_id' => 'pm_test'
        ]));
        
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [Response::HTTP_UNAUTHORIZED, Response::HTTP_FORBIDDEN, Response::HTTP_BAD_REQUEST]),
            'Purchase endpoint should require authentication'
        );
    }

    public function testPurchaseTicketRequiresValidJsonPayload(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/tickets/purchase', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], 'invalid-json');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetUserTicketsRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/tickets/my-tickets');
        
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [Response::HTTP_UNAUTHORIZED, Response::HTTP_FORBIDDEN]),
            'My tickets endpoint should require authentication'
        );
    }

    public function testCancelTicketRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('DELETE', '/api/tickets/some-uuid');
        
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [Response::HTTP_UNAUTHORIZED, Response::HTTP_FORBIDDEN, Response::HTTP_NOT_FOUND]),
            'Cancel ticket endpoint should require authentication'
        );
    }
}
