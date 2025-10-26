<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Event;
use App\Entity\Ticket;
use App\Entity\TicketType;
use App\Entity\User;
use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TicketRepositoryTest extends KernelTestCase
{
    private TicketRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->repository = $container->get(TicketRepository::class);
    }

    public function testRepositoryExists(): void
    {
        $this->assertInstanceOf(TicketRepository::class, $this->repository);
    }

    public function testFindByUser(): void
    {
        // This is a smoke test to ensure the repository method exists
        $user = (new User())
            ->setEmail('repo-user-'.uniqid().'@test.com')
            ->setPassword('password')
            ->setFirstName('Repo')
            ->setLastName('User');
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($user);
        $em->flush();
        
        $result = $this->repository->findBy(['user' => $user]);
        
        $this->assertIsArray($result);
    }

    public function testCountByTicketTypeAndStatus(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $organizer = (new User())
            ->setEmail('repo-org-'.uniqid().'@test.com')
            ->setPassword('password')
            ->setFirstName('Org')
            ->setLastName('User');
        $em->persist($organizer);
        $event = (new Event())
            ->setName('Repo Event')
            ->setDescription('Test')
            ->setVenue('V')
            ->setEventDate(new \DateTime('+1 day'))
            ->setMaxTickets(10)
            ->setStatus(Event::STATUS_PUBLISHED)
            ->setOrganizer($organizer);
        $em->persist($event);
        $ticketType = (new TicketType())
            ->setName('GA')
            ->setPrice(1000)
            ->setQuantity(10)
            ->setEvent($event);
        $em->persist($ticketType);
        $em->flush();
        
        $count = $this->repository->count([
            'ticketType' => $ticketType,
            'status' => [Ticket::STATUS_PURCHASED, Ticket::STATUS_RESERVED]
        ]);
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }
}
