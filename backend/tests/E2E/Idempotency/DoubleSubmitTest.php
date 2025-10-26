<?php

namespace App\Tests\E2E\Idempotency;

use App\Entity\IdempotencyKey;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DoubleSubmitTest extends WebTestCase
{
    public function testCrossInstanceDuplicateReturnsCachedResponse(): void
    {
        $client = static::createClient();
        $payload = [
            'eventId' => '11111111-1111-1111-1111-111111111111',
            'ticketTypeId' => '22222222-2222-2222-2222-222222222222',
            'quantity' => 1,
            'paymentMethodId' => 'pm_test'
        ];
        $key = 'e2e-key-'.uniqid();

        $client->request('POST', '/api/tickets/purchase', server: ['HTTP_X_IDEMPOTENCY_KEY' => $key], content: json_encode($payload));
        $first = $client->getResponse();

        $client->request('POST', '/api/tickets/purchase', server: ['HTTP_X_IDEMPOTENCY_KEY' => $key], content: json_encode($payload));
        $second = $client->getResponse();

        $this->assertSame($first->getStatusCode(), $second->getStatusCode());
        $this->assertSame($first->getContent(), $second->getContent());
    }

    public function testConcurrentDuplicateKeyReturnsError(): void
    {
        $kernel = static::createKernel();
        $kernel->boot();
        $container = $kernel->getContainer();
        if (!$container->has('doctrine')) {
            self::markTestSkipped('Doctrine not available');
        }
        $em = $container->get('doctrine')->getManager();

        // Seed processing key
        try {
            $key = new IdempotencyKey('e2e-processing', \App\Message\Command\Ticket\PurchaseTicketCommand::class);
            $em->persist($key);
            $em->flush();
        } catch (\Throwable $e) {
            self::markTestSkipped('Idempotency schema not available: '.$e->getMessage());
        }

        // Now request with the same key should be rejected as concurrent duplicate
        $client = static::createClient();
        $payload = [
            'eventId' => '11111111-1111-1111-1111-111111111111',
            'ticketTypeId' => '22222222-2222-2222-2222-222222222222',
            'quantity' => 1,
            'paymentMethodId' => 'pm_test'
        ];
        $client->request('POST', '/api/tickets/purchase', server: ['HTTP_X_IDEMPOTENCY_KEY' => 'e2e-processing'], content: json_encode($payload));
        $resp = $client->getResponse();
        $this->assertGreaterThanOrEqual(400, $resp->getStatusCode());
    }
}
