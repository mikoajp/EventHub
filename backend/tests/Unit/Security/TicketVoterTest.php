<?php

namespace App\Tests\Unit\Security;

use App\Entity\Event;
use App\Entity\Ticket;
use App\Entity\User;
use App\Security\Voter\TicketVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class TicketVoterTest extends TestCase
{
    private TicketVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new TicketVoter();
    }

    public function testUserCanViewOwnTicket(): void
    {
        $user = $this->createUser('user@test.com');
        $ticket = $this->createTicket($user);
        $token = $this->createToken($user);

        $result = $this->voter->vote($token, $ticket, [TicketVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCannotViewOthersTicket(): void
    {
        $owner = $this->createUser('owner@test.com');
        $otherUser = $this->createUser('other@test.com');
        $ticket = $this->createTicket($owner);
        $token = $this->createToken($otherUser);

        $result = $this->voter->vote($token, $ticket, [TicketVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testEventOrganizerCanViewTicketsForTheirEvent(): void
    {
        $organizer = $this->createUser('organizer@test.com');
        $buyer = $this->createUser('buyer@test.com');
        $ticket = $this->createTicket($buyer, $organizer);
        $token = $this->createToken($organizer);

        $result = $this->voter->vote($token, $ticket, [TicketVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testAdminCanViewAnyTicket(): void
    {
        $admin = $this->createUser('admin@test.com', ['ROLE_ADMIN']);
        $owner = $this->createUser('owner@test.com');
        $ticket = $this->createTicket($owner);
        $token = $this->createToken($admin);

        $result = $this->voter->vote($token, $ticket, [TicketVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCanCancelOwnTicketIfCancellable(): void
    {
        $user = $this->createUser('user@test.com');
        $ticket = $this->createCancellableTicket($user);
        $token = $this->createToken($user);

        $result = $this->voter->vote($token, $ticket, [TicketVoter::CANCEL]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCannotCancelTicketTooCloseToEvent(): void
    {
        $user = $this->createUser('user@test.com');
        $ticket = $this->createNonCancellableTicket($user);
        $token = $this->createToken($user);

        $result = $this->voter->vote($token, $ticket, [TicketVoter::CANCEL]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testUserCannotCancelAlreadyCancelledTicket(): void
    {
        $user = $this->createUser('user@test.com');
        
        // Create ticket with cancelled status directly
        $event = $this->createMock(Event::class);
        $event->method('getOrganizer')->willReturn($this->createUser('default-organizer@test.com'));
        $event->method('getEventDate')->willReturn(new \DateTime('+30 days'));
        
        $ticket = $this->createMock(Ticket::class);
        $ticket->method('getUser')->willReturn($user);
        $ticket->method('getEvent')->willReturn($event);
        $ticket->method('getStatus')->willReturn(\App\Enum\TicketStatus::CANCELLED);
        
        $token = $this->createToken($user);

        $result = $this->voter->vote($token, $ticket, [TicketVoter::CANCEL]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testOrganizerCanRefundTicketForTheirEvent(): void
    {
        $organizer = $this->createUser('organizer@test.com', ['ROLE_ORGANIZER']);
        $buyer = $this->createUser('buyer@test.com');
        
        // Create event
        $event = $this->createMock(Event::class);
        $event->method('getOrganizer')->willReturn($organizer);
        $event->method('getEventDate')->willReturn(new \DateTime('+30 days'));
        
        // Create ticket with PURCHASED status
        $ticket = $this->createMock(Ticket::class);
        $ticket->method('getUser')->willReturn($buyer);
        $ticket->method('getEvent')->willReturn($event);
        $ticket->method('getStatus')->willReturn(\App\Enum\TicketStatus::PURCHASED);
        
        $token = $this->createToken($organizer);

        $result = $this->voter->vote($token, $ticket, [TicketVoter::REFUND]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testRegularUserCannotRefundTickets(): void
    {
        $user = $this->createUser('user@test.com');
        $ticket = $this->createTicket($user);
        $token = $this->createToken($user);

        $result = $this->voter->vote($token, $ticket, [TicketVoter::REFUND]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testUserCanTransferOwnTicketIfTransferable(): void
    {
        $user = $this->createUser('user@test.com');
        $ticket = $this->createTransferableTicket($user);
        $token = $this->createToken($user);

        $result = $this->voter->vote($token, $ticket, [TicketVoter::TRANSFER]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCannotTransferUsedTicket(): void
    {
        $user = $this->createUser('user@test.com');
        
        // Create ticket with used status directly
        $event = $this->createMock(Event::class);
        $event->method('getOrganizer')->willReturn($this->createUser('default-organizer@test.com'));
        $event->method('getEventDate')->willReturn(new \DateTime('+30 days'));
        
        $ticket = $this->createMock(Ticket::class);
        $ticket->method('getUser')->willReturn($user);
        $ticket->method('getEvent')->willReturn($event);
        $ticket->method('getStatus')->willReturn(\App\Enum\TicketStatus::USED);
        
        $token = $this->createToken($user);

        $result = $this->voter->vote($token, $ticket, [TicketVoter::TRANSFER]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    private function createUser(string $email, array $roles = ['ROLE_USER']): User
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn($email);
        $user->method('getRoles')->willReturn($roles);
        $user->method('hasRole')->willReturnCallback(function($role) use ($roles) {
            return in_array($role->value, $roles, true);
        });
        
        return $user;
    }

    private function createTicket(User $owner, ?User $organizer = null): Ticket
    {
        $event = $this->createMock(Event::class);
        
        if ($organizer) {
            $event->method('getOrganizer')->willReturn($organizer);
        } else {
            $event->method('getOrganizer')->willReturn($this->createUser('default-organizer@test.com'));
        }
        
        // Event in future
        $event->method('getEventDate')->willReturn(new \DateTime('+30 days'));

        $ticket = $this->createMock(Ticket::class);
        $ticket->method('getUser')->willReturn($owner);
        $ticket->method('getEvent')->willReturn($event);
        $ticket->method('getStatus')->willReturn(\App\Enum\TicketStatus::RESERVED);

        return $ticket;
    }

    private function createCancellableTicket(User $owner): Ticket
    {
        $event = $this->createMock(Event::class);
        $event->method('getEventDate')->willReturn(new \DateTime('+30 days')); // 30 days away
        $event->method('getOrganizer')->willReturn($this->createUser('organizer@test.com'));

        $ticket = $this->createMock(Ticket::class);
        $ticket->method('getUser')->willReturn($owner);
        $ticket->method('getEvent')->willReturn($event);
        $ticket->method('getStatus')->willReturn(\App\Enum\TicketStatus::RESERVED);

        return $ticket;
    }

    private function createNonCancellableTicket(User $owner): Ticket
    {
        $event = $this->createMock(Event::class);
        $event->method('getEventDate')->willReturn(new \DateTime('+12 hours')); // Too close
        $event->method('getOrganizer')->willReturn($this->createUser('organizer@test.com'));

        $ticket = $this->createMock(Ticket::class);
        $ticket->method('getUser')->willReturn($owner);
        $ticket->method('getEvent')->willReturn($event);
        $ticket->method('getStatus')->willReturn(\App\Enum\TicketStatus::RESERVED);

        return $ticket;
    }

    private function createTransferableTicket(User $owner): Ticket
    {
        $event = $this->createMock(Event::class);
        $event->method('getEventDate')->willReturn(new \DateTime('+5 days')); // More than 1 hour away
        $event->method('getOrganizer')->willReturn($this->createUser('organizer@test.com'));

        $ticket = $this->createMock(Ticket::class);
        $ticket->method('getUser')->willReturn($owner);
        $ticket->method('getEvent')->willReturn($event);
        $ticket->method('getStatus')->willReturn(\App\Enum\TicketStatus::PURCHASED);

        return $ticket;
    }

    private function createToken(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        
        return $token;
    }
}
