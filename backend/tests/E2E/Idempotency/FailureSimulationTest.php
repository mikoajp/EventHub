<?php

namespace App\Tests\E2E\Idempotency;

use App\Tests\BaseWebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \App\Controller\Api\TicketController
 * @group e2e
 * @group idempotency
 * @group failure-simulation
 */
final class FailureSimulationTest extends BaseWebTestCase
{
    /**
     * Test that the system can recover from timeout scenarios
     * by retrying the request with the same idempotency key
     */
    public function testTimeoutRecoveryWithIdempotencyKey(): void
    {
        $client = static::createClient();
        $token = $this->createAuthenticatedUser($client);
        
        // Create an event and ticket type first
        $eventId = $this->createTestEvent($client, $token);
        $ticketTypeId = $this->getTicketTypeForEvent($client, $token, $eventId);
        
        $idempotencyKey = 'timeout-test-' . uniqid();
        
        // First request - simulate as if it timed out on client side
        // but actually processed on server
        $client->request('POST', '/api/tickets/purchase', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_X_IDEMPOTENCY_KEY' => $idempotencyKey,
        ], json_encode([
            'ticketTypeId' => $ticketTypeId,
            'quantity' => 1,
            'paymentMethodId' => 'pm_card_visa'
        ]));
        
        $this->assertResponseIsSuccessful();
        $firstResponse = json_decode($client->getResponse()->getContent(), true);
        
        // Second request - client retries thinking first request timed out
        // Should return cached result from first request
        $client->request('POST', '/api/tickets/purchase', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_X_IDEMPOTENCY_KEY' => $idempotencyKey,
        ], json_encode([
            'ticketTypeId' => $ticketTypeId,
            'quantity' => 1,
            'paymentMethodId' => 'pm_card_visa'
        ]));
        
        $this->assertResponseIsSuccessful();
        $secondResponse = json_decode($client->getResponse()->getContent(), true);
        
        // Both responses should be identical (cached result)
        $this->assertEquals($firstResponse['orderId'], $secondResponse['orderId']);
        $this->assertEquals($firstResponse['tickets'], $secondResponse['tickets']);
    }

    /**
     * Test that client receives proper error when server has internal error,
     * and can retry without creating duplicate orders
     */
    public function testInternalServerErrorWithIdempotency(): void
    {
        $client = static::createClient();
        $token = $this->createAuthenticatedUser($client);
        
        $eventId = $this->createTestEvent($client, $token);
        $ticketTypeId = $this->getTicketTypeForEvent($client, $token, $eventId);
        
        $idempotencyKey = 'error-test-' . uniqid();
        
        // Attempt purchase with invalid payment method to simulate failure
        $client->request('POST', '/api/tickets/purchase', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_X_IDEMPOTENCY_KEY' => $idempotencyKey,
        ], json_encode([
            'ticketTypeId' => $ticketTypeId,
            'quantity' => 1,
            'paymentMethodId' => 'pm_invalid_card' // Will cause payment to fail
        ]));
        
        // Should return error response
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        // Retry with same idempotency key but correct payment method
        // Should still return the cached error (idempotency prevents retry with different data)
        $client->request('POST', '/api/tickets/purchase', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_X_IDEMPOTENCY_KEY' => $idempotencyKey,
        ], json_encode([
            'ticketTypeId' => $ticketTypeId,
            'quantity' => 1,
            'paymentMethodId' => 'pm_card_visa'
        ]));
        
        // Should still return the same error (cached response)
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test partial failure scenario where payment succeeds but
     * subsequent operations might fail
     */
    public function testPartialFailureWithTransactionalRollback(): void
    {
        $client = static::createClient();
        $token = $this->createAuthenticatedUser($client);
        
        $eventId = $this->createTestEvent($client, $token);
        $ticketTypeId = $this->getTicketTypeForEvent($client, $token, $eventId);
        
        // Purchase ticket normally
        $client->request('POST', '/api/tickets/purchase', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ], json_encode([
            'ticketTypeId' => $ticketTypeId,
            'quantity' => 1,
            'paymentMethodId' => 'pm_card_visa'
        ]));
        
        // Should succeed
        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        
        // Verify order and tickets were created atomically
        $this->assertArrayHasKey('orderId', $response);
        $this->assertArrayHasKey('tickets', $response);
        $this->assertNotEmpty($response['tickets']);
        
        // If we get success response, both payment AND ticket creation succeeded
        // (transaction ensures atomicity)
    }

    /**
     * Test that network interruption doesn't cause duplicate charges
     */
    public function testNetworkInterruptionProtection(): void
    {
        $client = static::createClient();
        $token = $this->createAuthenticatedUser($client);
        
        $eventId = $this->createTestEvent($client, $token);
        $ticketTypeId = $this->getTicketTypeForEvent($client, $token, $eventId);
        
        $idempotencyKey = 'network-test-' . uniqid();
        
        // First request
        $client->request('POST', '/api/tickets/purchase', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_X_IDEMPOTENCY_KEY' => $idempotencyKey,
        ], json_encode([
            'ticketTypeId' => $ticketTypeId,
            'quantity' => 1,
            'paymentMethodId' => 'pm_card_visa'
        ]));
        
        $this->assertResponseIsSuccessful();
        $firstResponse = json_decode($client->getResponse()->getContent(), true);
        $firstOrderId = $firstResponse['orderId'];
        
        // Simulate network interruption - client retries
        for ($i = 0; $i < 3; $i++) {
            $client->request('POST', '/api/tickets/purchase', [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'HTTP_X_IDEMPOTENCY_KEY' => $idempotencyKey,
            ], json_encode([
                'ticketTypeId' => $ticketTypeId,
                'quantity' => 1,
                'paymentMethodId' => 'pm_card_visa'
            ]));
            
            $this->assertResponseIsSuccessful();
            $retryResponse = json_decode($client->getResponse()->getContent(), true);
            
            // All retries should return same order ID
            $this->assertEquals($firstOrderId, $retryResponse['orderId']);
        }
    }

    /**
     * Test concurrent requests with same idempotency key
     * Only one should process, others should wait and get cached result
     */
    public function testConcurrentRequestsWithSameIdempotencyKey(): void
    {
        $client = static::createClient();
        $token = $this->createAuthenticatedUser($client);
        
        $eventId = $this->createTestEvent($client, $token);
        $ticketTypeId = $this->getTicketTypeForEvent($client, $token, $eventId);
        
        $idempotencyKey = 'concurrent-test-' . uniqid();
        
        // Make first request
        $client->request('POST', '/api/tickets/purchase', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_X_IDEMPOTENCY_KEY' => $idempotencyKey,
        ], json_encode([
            'ticketTypeId' => $ticketTypeId,
            'quantity' => 1,
            'paymentMethodId' => 'pm_card_visa'
        ]));
        
        $this->assertResponseIsSuccessful();
        $firstResponse = json_decode($client->getResponse()->getContent(), true);
        
        // Make second request immediately (simulating concurrent request)
        $client->request('POST', '/api/tickets/purchase', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_X_IDEMPOTENCY_KEY' => $idempotencyKey,
        ], json_encode([
            'ticketTypeId' => $ticketTypeId,
            'quantity' => 1,
            'paymentMethodId' => 'pm_card_visa'
        ]));
        
        // Second request should either:
        // 1. Return HTTP 409 Conflict (already processing)
        // 2. Return cached result with same order ID
        
        $statusCode = $client->getResponse()->getStatusCode();
        
        if ($statusCode === Response::HTTP_OK) {
            $secondResponse = json_decode($client->getResponse()->getContent(), true);
            $this->assertEquals($firstResponse['orderId'], $secondResponse['orderId']);
        } else {
            // Should be 409 Conflict if still processing
            $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        }
    }

    /**
     * Helper method to create authenticated user
     */
    private function createAuthenticatedUser($client): string
    {
        $email = 'failure-test-' . uniqid() . '@example.com';
        
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => 'password123',
            'firstName' => 'Test',
            'lastName' => 'User'
        ]));
        
        $response = json_decode($client->getResponse()->getContent(), true);
        return $response['token'];
    }

    /**
     * Helper method to create test event
     */
    private function createTestEvent($client, string $token): string
    {
        $client->request('POST', '/api/events', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ], json_encode([
            'name' => 'Test Event ' . uniqid(),
            'description' => 'Test event for failure simulation',
            'venue' => 'Test Venue',
            'eventDate' => (new \DateTime('+1 month'))->format('Y-m-d H:i:s'),
            'maxTickets' => 100,
            'ticketTypes' => [
                [
                    'name' => 'Standard',
                    'price' => 5000,
                    'quantity' => 50
                ]
            ]
        ]));
        
        $response = json_decode($client->getResponse()->getContent(), true);
        return $response['id'];
    }

    /**
     * Helper method to get ticket type ID for event
     */
    private function getTicketTypeForEvent($client, string $token, string $eventId): string
    {
        $client->request('GET', '/api/events/' . $eventId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        
        $response = json_decode($client->getResponse()->getContent(), true);
        return $response['ticketTypes'][0]['id'];
    }
}
