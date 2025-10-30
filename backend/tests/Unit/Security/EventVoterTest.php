<?php

namespace App\Tests\Unit\Security;

use App\Domain\Event\Service\EventCalculationService;
use App\Domain\Event\Service\EventDomainService;
use App\Entity\Event;
use App\Entity\User;
use App\Security\Voter\EventVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Test voter authorization rules
 */
final class EventVoterTest extends TestCase
{
    private EventDomainService $domainService;
    private EventCalculationService $calculationService;
    private EventVoter $voter;

    protected function setUp(): void
    {
        $this->domainService = $this->createMock(EventDomainService::class);
        $this->calculationService = $this->createMock(EventCalculationService::class);
        $this->voter = new EventVoter($this->domainService, $this->calculationService);
    }

    public function testAnonymousCanViewPublishedEvents(): void
    {
        $event = $this->createEvent(Event::STATUS_PUBLISHED);
        $token = $this->createTokenWithoutUser();

        $result = $this->voter->vote($token, $event, [EventVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testAnonymousCannotViewDraftEvents(): void
    {
        $event = $this->createEvent(Event::STATUS_DRAFT);
        $token = $this->createTokenWithoutUser();

        $result = $this->voter->vote($token, $event, [EventVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testOrganizerCanViewOwnDraftEvent(): void
    {
        $organizer = $this->createUser('organizer@test.com', ['ROLE_ORGANIZER']);
        $event = $this->createEvent(Event::STATUS_DRAFT, $organizer);
        $token = $this->createToken($organizer);

        $result = $this->voter->vote($token, $event, [EventVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOrganizerCannotViewOthersDraftEvent(): void
    {
        $organizer = $this->createUser('organizer@test.com', ['ROLE_ORGANIZER']);
        $otherOrganizer = $this->createUser('other@test.com', ['ROLE_ORGANIZER']);
        $event = $this->createEvent(Event::STATUS_DRAFT, $otherOrganizer);
        $token = $this->createToken($organizer);

        $result = $this->voter->vote($token, $event, [EventVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAdminCanViewAnyEvent(): void
    {
        $admin = $this->createUser('admin@test.com', ['ROLE_ADMIN']);
        $organizer = $this->createUser('organizer@test.com', ['ROLE_ORGANIZER']);
        $event = $this->createEvent(Event::STATUS_DRAFT, $organizer);
        $token = $this->createToken($admin);

        $result = $this->voter->vote($token, $event, [EventVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOrganizerCanEditOwnEvent(): void
    {
        $organizer = $this->createUser('organizer@test.com', ['ROLE_ORGANIZER']);
        $event = $this->createEvent(Event::STATUS_DRAFT, $organizer);
        $token = $this->createToken($organizer);

        $this->domainService->expects($this->once())
            ->method('canBeModified')
            ->willReturn(true);

        $this->calculationService->expects($this->once())
            ->method('calculateTicketsSold')
            ->willReturn(0);

        $result = $this->voter->vote($token, $event, [EventVoter::EDIT]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOrganizerCannotEditEventWithSoldTickets(): void
    {
        $organizer = $this->createUser('organizer@test.com', ['ROLE_ORGANIZER']);
        $event = $this->createEvent(Event::STATUS_PUBLISHED, $organizer);
        $token = $this->createToken($organizer);

        $this->calculationService->expects($this->once())
            ->method('calculateTicketsSold')
            ->willReturn(10); // Tickets sold

        $this->domainService->expects($this->once())
            ->method('canBeModified')
            ->willReturn(false);

        $result = $this->voter->vote($token, $event, [EventVoter::EDIT]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testOrganizerCanDeleteEventWithNoTicketsSold(): void
    {
        $organizer = $this->createUser('organizer@test.com', ['ROLE_ORGANIZER']);
        $event = $this->createEvent(Event::STATUS_DRAFT, $organizer);
        $token = $this->createToken($organizer);

        $this->calculationService->expects($this->once())
            ->method('calculateTicketsSold')
            ->willReturn(0);

        $this->domainService->expects($this->once())
            ->method('hasTicketsSold')
            ->willReturn(false);

        $result = $this->voter->vote($token, $event, [EventVoter::DELETE]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOrganizerCannotDeleteEventWithTicketsSold(): void
    {
        $organizer = $this->createUser('organizer@test.com', ['ROLE_ORGANIZER']);
        $event = $this->createEvent(Event::STATUS_PUBLISHED, $organizer);
        $token = $this->createToken($organizer);

        $this->calculationService->expects($this->once())
            ->method('calculateTicketsSold')
            ->willReturn(5);

        $this->domainService->expects($this->once())
            ->method('hasTicketsSold')
            ->willReturn(true);

        $result = $this->voter->vote($token, $event, [EventVoter::DELETE]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testOrganizerCanPublishOwnDraftEvent(): void
    {
        $organizer = $this->createUser('organizer@test.com', ['ROLE_ORGANIZER']);
        $event = $this->createEvent(Event::STATUS_DRAFT, $organizer);
        $token = $this->createToken($organizer);

        $this->domainService->expects($this->once())
            ->method('canBePublished')
            ->willReturn(true);

        $result = $this->voter->vote($token, $event, [EventVoter::PUBLISH]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOrganizerCanCancelOwnEvent(): void
    {
        $organizer = $this->createUser('organizer@test.com', ['ROLE_ORGANIZER']);
        $event = $this->createEvent(Event::STATUS_PUBLISHED, $organizer);
        $token = $this->createToken($organizer);

        $this->domainService->expects($this->once())
            ->method('canBeCancelled')
            ->willReturn(true);

        $result = $this->voter->vote($token, $event, [EventVoter::CANCEL]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testRegularUserCannotEditEvent(): void
    {
        $user = $this->createUser('user@test.com', ['ROLE_USER']);
        $organizer = $this->createUser('organizer@test.com', ['ROLE_ORGANIZER']);
        $event = $this->createEvent(Event::STATUS_PUBLISHED, $organizer);
        $token = $this->createToken($user);

        $result = $this->voter->vote($token, $event, [EventVoter::EDIT]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testOrganizerCanViewStatisticsForOwnEvent(): void
    {
        $organizer = $this->createUser('organizer@test.com', ['ROLE_ORGANIZER']);
        $event = $this->createEvent(Event::STATUS_PUBLISHED, $organizer);
        $token = $this->createToken($organizer);

        $result = $this->voter->vote($token, $event, [EventVoter::STATISTICS]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOrganizerCannotViewStatisticsForOthersEvent(): void
    {
        $organizer = $this->createUser('organizer@test.com', ['ROLE_ORGANIZER']);
        $otherOrganizer = $this->createUser('other@test.com', ['ROLE_ORGANIZER']);
        $event = $this->createEvent(Event::STATUS_PUBLISHED, $otherOrganizer);
        $token = $this->createToken($organizer);

        $result = $this->voter->vote($token, $event, [EventVoter::STATISTICS]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    private function createEvent(string $status, ?User $organizer = null): Event
    {
        $event = $this->createMock(Event::class);
        $event->method('getStatus')->willReturn(\App\Enum\EventStatus::from($status));
        $event->method('isPublished')->willReturn($status === Event::STATUS_PUBLISHED);
        
        if ($organizer) {
            $event->method('getOrganizer')->willReturn($organizer);
        } else {
            // Return a default organizer for anonymous access tests
            $event->method('getOrganizer')->willReturn($this->createUser('default@test.com'));
        }

        return $event;
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

    private function createToken(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        
        return $token;
    }

    private function createTokenWithoutUser(): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        
        return $token;
    }
}
