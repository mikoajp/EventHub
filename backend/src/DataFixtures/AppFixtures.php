<?php
namespace App\DataFixtures;

use App\Entity\Event;
use App\Entity\TicketType;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Create admin user
        $admin = new User();
        $admin->setEmail('admin@example.com')
              ->setFirstName('Admin')
              ->setLastName('User')
              ->setRoles(['ROLE_ADMIN', 'ROLE_ORGANIZER'])
              ->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        
        $manager->persist($admin);

        // Create regular user
        $user = new User();
        $user->setEmail('user@example.com')
             ->setFirstName('John')
             ->setLastName('Doe')
             ->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        
        $manager->persist($user);

        // Create sample events
        $events = [
            [
                'name' => 'Summer Music Festival',
                'description' => 'The biggest music festival of the summer featuring top artists from around the world.',
                'venue' => 'Central Park, New York',
                'maxTickets' => 5000,
                'ticketTypes' => [
                    ['name' => 'General Admission', 'price' => 8500, 'quantity' => 4000],
                    ['name' => 'VIP', 'price' => 15000, 'quantity' => 800],
                    ['name' => 'Premium', 'price' => 25000, 'quantity' => 200],
                ]
            ],
            [
                'name' => 'Tech Conference 2024',
                'description' => 'Join industry leaders and innovators for a day of cutting-edge technology discussions.',
                'venue' => 'Convention Center, San Francisco',
                'maxTickets' => 1500,
                'ticketTypes' => [
                    ['name' => 'Standard', 'price' => 29900, 'quantity' => 1200],
                    ['name' => 'Premium', 'price' => 49900, 'quantity' => 300],
                ]
            ],
            [
                'name' => 'Food & Wine Expo',
                'description' => 'Discover the finest culinary delights and premium wines from local and international vendors.',
                'venue' => 'Exhibition Hall, Chicago',
                'maxTickets' => 800,
                'ticketTypes' => [
                    ['name' => 'Tasting Pass', 'price' => 6500, 'quantity' => 600],
                    ['name' => 'VIP Experience', 'price' => 12500, 'quantity' => 200],
                ]
            ]
        ];

        foreach ($events as $eventData) {
            $event = new Event();
            $event->setName($eventData['name'])
                  ->setDescription($eventData['description'])
                  ->setEventDate(new \DateTimeImmutable('+' . rand(7, 90) . ' days'))
                  ->setVenue($eventData['venue'])
                  ->setMaxTickets($eventData['maxTickets'])
                  ->setOrganizer($admin)
                  ->setStatus(Event::STATUS_PUBLISHED);

            foreach ($eventData['ticketTypes'] as $ticketTypeData) {
                $ticketType = new TicketType();
                $ticketType->setName($ticketTypeData['name'])
                           ->setPrice($ticketTypeData['price'])
                           ->setQuantity($ticketTypeData['quantity'])
                           ->setEvent($event);
                
                $event->addTicketType($ticketType);
            }

            $manager->persist($event);
        }

        $manager->flush();
    }
}