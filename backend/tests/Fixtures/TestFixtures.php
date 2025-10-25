<?php

namespace App\Tests\Fixtures;

use App\Entity\Event;
use App\Entity\Order;
use App\Entity\Ticket;
use App\Entity\TicketType;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Test fixtures for integration and functional tests
 */
class TestFixtures extends Fixture
{
    public const USER_REFERENCE = 'test-user';
    public const ADMIN_REFERENCE = 'test-admin';
    public const EVENT_REFERENCE = 'test-event';
    public const TICKET_TYPE_REFERENCE = 'test-ticket-type';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Create test users
        $user = $this->createUser('test@example.com', ['ROLE_USER']);
        $admin = $this->createUser('admin@example.com', ['ROLE_USER', 'ROLE_ADMIN']);
        
        $manager->persist($user);
        $manager->persist($admin);

        // Create test event
        $event = $this->createEvent($admin);
        $manager->persist($event);

        // Create ticket types
        $ticketTypeVip = $this->createTicketType($event, 'VIP', 10000, 50);
        $ticketTypeStandard = $this->createTicketType($event, 'Standard', 5000, 200);
        
        $manager->persist($ticketTypeVip);
        $manager->persist($ticketTypeStandard);

        // Create some tickets
        $ticket = $this->createTicket($user, $event, $ticketTypeStandard);
        $manager->persist($ticket);

        $manager->flush();

        // Add references
        $this->addReference(self::USER_REFERENCE, $user);
        $this->addReference(self::ADMIN_REFERENCE, $admin);
        $this->addReference(self::EVENT_REFERENCE, $event);
        $this->addReference(self::TICKET_TYPE_REFERENCE, $ticketTypeStandard);
    }

    private function createUser(string $email, array $roles): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setFirstName('Test');
        $user->setLastName('User');
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
        $user->setPassword($hashedPassword);

        return $user;
    }

    private function createEvent(User $organizer): Event
    {
        $event = new Event();
        $event->setName('Test Event');
        $event->setDescription('Test event description');
        $event->setVenue('Test Venue');
        $event->setEventDate(new \DateTime('+1 month'));
        $event->setMaxTickets(250);
        $event->setOrganizer($organizer);
        $event->setStatus(Event::STATUS_PUBLISHED);

        return $event;
    }

    private function createTicketType(Event $event, string $name, int $price, int $quantity): TicketType
    {
        $ticketType = new TicketType();
        $ticketType->setName($name);
        $ticketType->setPrice($price);
        $ticketType->setQuantity($quantity);
        $ticketType->setEvent($event);

        return $ticketType;
    }

    private function createTicket(User $user, Event $event, TicketType $ticketType): Ticket
    {
        $ticket = new Ticket();
        $ticket->setUser($user);
        $ticket->setEvent($event);
        $ticket->setTicketType($ticketType);
        $ticket->setPrice($ticketType->getPrice());
        $ticket->setStatus(Ticket::STATUS_PURCHASED);
        $ticket->setPurchasedAt(new \DateTimeImmutable());

        return $ticket;
    }
}
