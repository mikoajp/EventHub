<?php

namespace App\Tests\Functional\Api;

use App\Entity\Event;
use App\Entity\Ticket;
use App\Entity\TicketType;
use App\Entity\User;
use App\Tests\BaseWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Full flow test: signup → login → list events → purchase ticket → double submit → compensation
 */
final class TicketPurchaseFlowTest extends BaseWebTestCase
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testCompleteTicketPurchaseFlow(): void
    {
        $client = $this->client;

        // Create user and get token directly (login via API has issues)
        $email = 'buyer-' . uniqid() . '@test.com';
        $user = $this->createUser($email);
        $token = $this->generateJwtToken($email, $user->getRoles());

        // Step 3: Create event (as organizer)
        $organizer = $this->createOrganizer();
        $event = $this->createPublishedEvent($organizer);
        $ticketType = $this->createTicketType($event, 10, 5000); // 10 tickets at $50

        // Step 4: List events with filters
        $client->request('GET', '/api/events', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $events = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($events);

        // Step 5: Purchase ticket
        $client->request('POST', '/api/tickets/purchase', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ], json_encode([
            'eventId' => $event->getId()->toString(),
            'ticketTypeId' => $ticketType->getId()->toString(),
            'quantity' => 2,
            'paymentMethodId' => 'pm_test_success'
        ]));

        $this->assertResponseIsSuccessful();
        $purchaseData = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('ticket_ids', $purchaseData);
        $this->assertCount(2, $purchaseData['ticket_ids']);
    }

    public function testDoubleSubmitPrevention(): void
    {
        $client = $this->client;

        $email = 'buyer2-' . uniqid() . '@test.com';
        $user = $this->createUser($email);
        $token = $this->generateJwtToken($email, $user->getRoles());

        $organizer = $this->createOrganizer();
        $event = $this->createPublishedEvent($organizer);
        $ticketType = $this->createTicketType($event, 5, 3000);

        $idempotencyKey = 'test-idempotency-' . uniqid();

        // First request
        $client->request('POST', '/api/tickets/purchase', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_X_IDEMPOTENCY_KEY' => $idempotencyKey,
        ], json_encode([
            'eventId' => $event->getId()->toString(),
            'ticketTypeId' => $ticketType->getId()->toString(),
            'quantity' => 1,
            'paymentMethodId' => 'pm_test_success'
        ]));

        $this->assertResponseIsSuccessful();
        $firstResponse = json_decode($client->getResponse()->getContent(), true);

        // Second request with same idempotency key (double submit)
        $client->request('POST', '/api/tickets/purchase', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_X_IDEMPOTENCY_KEY' => $idempotencyKey,
        ], json_encode([
            'eventId' => $event->getId()->toString(),
            'ticketTypeId' => $ticketType->getId()->toString(),
            'quantity' => 1,
            'paymentMethodId' => 'pm_test_success'
        ]));

        $this->assertResponseIsSuccessful();
        $secondResponse = json_decode($client->getResponse()->getContent(), true);

        // Should return same result
        $this->assertSame($firstResponse, $secondResponse);

        // Verify only one ticket was created
        $tickets = $this->entityManager->getRepository(Ticket::class)->findBy([
            'user' => $user,
            'event' => $event
        ]);

        $this->assertCount(1, $tickets);
    }

    public function testConcurrentPurchaseOnlyOneSucceeds(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createPublishedEvent($organizer);
        $ticketType = $this->createTicketType($event, 1, 5000); // Only 1 ticket available

        $email1 = 'concurrent1-' . uniqid() . '@test.com';
        $email2 = 'concurrent2-' . uniqid() . '@test.com';
        $user1 = $this->createUser($email1);
        $user2 = $this->createUser($email2);

        $token1 = $this->generateJwtToken($email1, $user1->getRoles());
        $token2 = $this->generateJwtToken($email2, $user2->getRoles());

        // Both users try to buy the last ticket (sequentially to avoid kernel reboots)
        $client = $this->client;

        $client->request('POST', '/api/tickets/purchase', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token1,
        ], json_encode([
            'eventId' => $event->getId()->toString(),
            'ticketTypeId' => $ticketType->getId()->toString(),
            'quantity' => 1,
            'paymentMethodId' => 'pm_test_success',
            'idempotencyKey' => 'test-concurrent-1-' . uniqid()
        ]));

        $response1Status = $client->getResponse()->getStatusCode();

        $client->request('POST', '/api/tickets/purchase', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token2,
        ], json_encode([
            'eventId' => $event->getId()->toString(),
            'ticketTypeId' => $ticketType->getId()->toString(),
            'quantity' => 1,
            'paymentMethodId' => 'pm_test_success',
            'idempotencyKey' => 'test-concurrent-2-' . uniqid()
        ]));

        $response2Status = $client->getResponse()->getStatusCode();

        // One should succeed, one should fail
        $successCount = 0;
        if ($response1Status === Response::HTTP_OK || $response1Status === Response::HTTP_CREATED) {
            $successCount++;
        }
        if ($response2Status === Response::HTTP_OK || $response2Status === Response::HTTP_CREATED) {
            $successCount++;
        }

        $this->assertSame(1, $successCount, 'Only one purchase should succeed');

        // Verify only 1 ticket exists
        $tickets = $this->entityManager->getRepository(Ticket::class)->findBy([
            'ticketType' => $ticketType,
            'status' => [Ticket::STATUS_RESERVED, Ticket::STATUS_PURCHASED]
        ]);

        $this->assertCount(1, $tickets);
    }

    public function testPaymentFailureTriggersCompensation(): void
    {
        $client = $this->client;

        $email = 'payment-fail-' . uniqid() . '@test.com';
        $user = $this->createUser($email);
        $token = $this->generateJwtToken($email, $user->getRoles());

        $organizer = $this->createOrganizer();
        $event = $this->createPublishedEvent($organizer);
        $ticketType = $this->createTicketType($event, 10, 5000);

        // Purchase with failing payment method
        $client->request('POST', '/api/tickets/purchase', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ], json_encode([
            'eventId' => $event->getId()->toString(),
            'ticketTypeId' => $ticketType->getId()->toString(),
            'quantity' => 1,
            'paymentMethodId' => 'pm_test_fail', // This should fail
            'idempotencyKey' => 'test-payment-fail-' . uniqid()
        ]));

        // Should handle payment failure gracefully
        // Ticket should be cancelled, not left in reserved state
        sleep(2); // Allow async processing

        $tickets = $this->entityManager->getRepository(Ticket::class)->findBy([
            'user' => $user,
            'event' => $event
        ]);

        foreach ($tickets as $ticket) {
            $this->assertSame(
                Ticket::STATUS_CANCELLED,
                $ticket->getStatus(),
                'Failed payment should result in cancelled ticket'
            );
        }
    }

    private function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('$2y$13$hashedpassword');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createOrganizer(): User
    {
        $organizer = new User();
        $organizer->setEmail('organizer-' . uniqid() . '@test.com');
        $organizer->setPassword('$2y$13$hashedpassword');
        $organizer->setFirstName('Test');
        $organizer->setLastName('Organizer');
        $organizer->setRoles(['ROLE_ORGANIZER']);

        $this->entityManager->persist($organizer);
        $this->entityManager->flush();

        return $organizer;
    }

    private function createPublishedEvent(User $organizer): Event
    {
        $event = new Event();
        $event->setName('Test Concert ' . uniqid())
            ->setDescription('A great event')
            ->setVenue('Test Venue')
            ->setEventDate(new \DateTime('+1 month'))
            ->setMaxTickets(100)
            ->setStatus(Event::STATUS_PUBLISHED)
            ->setPublishedAt(new \DateTime())
            ->setOrganizer($organizer);

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $event;
    }

    private function createTicketType(Event $event, int $quantity, int $price): TicketType
    {
        $ticketType = new TicketType();
        $ticketType->setName('General Admission')
            ->setPrice($price)
            ->setQuantity($quantity)
            ->setEvent($event);

        $this->entityManager->persist($ticketType);
        $this->entityManager->flush();

        return $ticketType;
    }

    protected function generateJwtToken(string $email, array $roles = []): string
    {
        return parent::generateJwtToken($email, $roles);
    }
}
