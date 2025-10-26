<?php

namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class IdempotencyApiTest extends WebTestCase
{
    public function testDuplicateKeyReturnsCachedResponse(): void
    {
        $client = static::createClient();
        $payload = [
            'eventId' => '11111111-1111-1111-1111-111111111111',
            'ticketTypeId' => '22222222-2222-2222-2222-222222222222',
            'quantity' => 1,
            'paymentMethodId' => 'pm_test'
        ];
        $key = 'cli-test-key-123';

        $client->request('POST', '/api/tickets/purchase', server: ['HTTP_X-Idempotency-Key' => $key], content: json_encode($payload));
        $first = $client->getResponse();

        $client->request('POST', '/api/tickets/purchase', server: ['HTTP_X-Idempotency-Key' => $key], content: json_encode($payload));
        $second = $client->getResponse();

        $this->assertSame($first->getStatusCode(), $second->getStatusCode());
        $this->assertSame($first->getContent(), $second->getContent());
    }
}
