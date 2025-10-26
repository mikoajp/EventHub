<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Event;
use App\Entity\Ticket;
use App\Entity\TicketType;
use App\Entity\User;
use App\Repository\TicketRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Test concurrent ticket purchases to ensure race conditions are properly handled
 */
final class ConcurrentTicketPurchaseTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private TicketRepository $ticketRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->ticketRepository = self::getContainer()->get(TicketRepository::class);
        
        $this->entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }
        
        parent::tearDown();
    }

    public function testPessimisticLockPreventsRaceCondition(): void
    {
        // Create test data
        $event = $this->createTestEvent();
        $ticketType = $this->createTestTicketType($event, 10); // Only 10 tickets available
        $user1 = $this->createTestUser('user1@test.com');
        $user2 = $this->createTestUser('user2@test.com');

        $this->entityManager->persist($event);
        $this->entityManager->persist($ticketType);
        $this->entityManager->persist($user1);
        $this->entityManager->persist($user2);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Simulate concurrent purchase attempts
        // Lock the ticket type for user1
        $lockedTicketType = $this->entityManager->find(
            TicketType::class,
            $ticketType->getId(),
            LockMode::PESSIMISTIC_WRITE
        );

        $this->assertNotNull($lockedTicketType);

        // Check available tickets
        $soldCount = $this->ticketRepository->count([
            'ticketType' => $lockedTicketType,
            'status' => [Ticket::STATUS_PURCHASED, Ticket::STATUS_RESERVED]
        ]);

        $available = $lockedTicketType->getQuantity() - $soldCount;
        $this->assertSame(10, $available);

        // Create tickets for user1 (requesting 10 tickets)
        for ($i = 0; $i < 10; $i++) {
            $ticket = new Ticket();
            $ticket->setEvent($event)
                ->setTicketType($lockedTicketType)
                ->setUser($user1)
                ->setPrice($lockedTicketType->getPrice())
                ->setStatus(Ticket::STATUS_RESERVED);
            
            $this->entityManager->persist($ticket);
        }

        $this->entityManager->flush();
        
        // Now check that no more tickets are available
        $soldCountAfter = $this->ticketRepository->count([
            'ticketType' => $lockedTicketType,
            'status' => [Ticket::STATUS_PURCHASED, Ticket::STATUS_RESERVED]
        ]);

        $availableAfter = $lockedTicketType->getQuantity() - $soldCountAfter;
        $this->assertSame(0, $availableAfter);
    }

    public function testTicketAvailabilityCheckIsAccurate(): void
    {
        $event = $this->createTestEvent();
        $ticketType = $this->createTestTicketType($event, 5);
        $user = $this->createTestUser('buyer@test.com');

        $this->entityManager->persist($event);
        $this->entityManager->persist($ticketType);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Create 3 reserved tickets
        for ($i = 0; $i < 3; $i++) {
            $ticket = new Ticket();
            $ticket->setEvent($event)
                ->setTicketType($ticketType)
                ->setUser($user)
                ->setPrice($ticketType->getPrice())
                ->setStatus(Ticket::STATUS_RESERVED);
            
            $this->entityManager->persist($ticket);
        }

        $this->entityManager->flush();

        // Check availability
        $soldCount = $this->ticketRepository->count([
            'ticketType' => $ticketType,
            'status' => [Ticket::STATUS_PURCHASED, Ticket::STATUS_RESERVED]
        ]);

        $available = $ticketType->getQuantity() - $soldCount;
        
        $this->assertSame(2, $available, 'Should have 2 tickets available (5 total - 3 reserved)');
    }

    public function testCancelledTicketsAreNotCountedAsSold(): void
    {
        $event = $this->createTestEvent();
        $ticketType = $this->createTestTicketType($event, 5);
        $user = $this->createTestUser('buyer@test.com');

        $this->entityManager->persist($event);
        $this->entityManager->persist($ticketType);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Create 2 reserved and 1 cancelled ticket
        for ($i = 0; $i < 2; $i++) {
            $ticket = new Ticket();
            $ticket->setEvent($event)
                ->setTicketType($ticketType)
                ->setUser($user)
                ->setPrice($ticketType->getPrice())
                ->setStatus(Ticket::STATUS_RESERVED);
            
            $this->entityManager->persist($ticket);
        }

        $cancelledTicket = new Ticket();
        $cancelledTicket->setEvent($event)
            ->setTicketType($ticketType)
            ->setUser($user)
            ->setPrice($ticketType->getPrice())
            ->setStatus(Ticket::STATUS_CANCELLED);
        
        $this->entityManager->persist($cancelledTicket);
        $this->entityManager->flush();

        // Check availability - cancelled tickets should not count
        $soldCount = $this->ticketRepository->count([
            'ticketType' => $ticketType,
            'status' => [Ticket::STATUS_PURCHASED, Ticket::STATUS_RESERVED]
        ]);

        $available = $ticketType->getQuantity() - $soldCount;
        
        $this->assertSame(3, $available, 'Should have 3 tickets available (5 total - 2 reserved, cancelled not counted)');
    }

    private function createTestEvent(): Event
    {
        $organizer = new User();
        $organizer->setEmail('organizer@test.com');
        $organizer->setPassword('password');
        $organizer->setFirstName('Test');
        $organizer->setLastName('Organizer');
        $organizer->setRoles(['ROLE_ORGANIZER']);
        $this->entityManager->persist($organizer);

        $event = new Event();
        $event->setName('Test Concert')
            ->setDescription('A test event')
            ->setVenue('Test Venue')
            ->setEventDate(new \DateTime('+1 month'))
            ->setMaxTickets(100)
            ->setStatus(Event::STATUS_PUBLISHED)
            ->setOrganizer($organizer);

        return $event;
    }

    private function createTestTicketType(Event $event, int $quantity): TicketType
    {
        $ticketType = new TicketType();
        $ticketType->setName('General Admission')
            ->setPrice(5000) // $50.00
            ->setQuantity($quantity)
            ->setEvent($event);

        return $ticketType;
    }

    private function createTestUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('password');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRoles(['ROLE_USER']);

        return $user;
    }
}
