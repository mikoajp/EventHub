<?php

namespace App\Security\Voter;

use App\Domain\Event\Service\EventCalculationService;
use App\Domain\Event\Service\EventDomainService;
use App\Entity\Event;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EventVoter extends Voter
{
    public function __construct(
        private readonly EventDomainService $domainService,
        private readonly EventCalculationService $calculationService
    ) {}

    public const VIEW = 'EVENT_VIEW';
    public const EDIT = 'EVENT_EDIT';
    public const DELETE = 'EVENT_DELETE';
    public const PUBLISH = 'EVENT_PUBLISH';
    public const CANCEL = 'EVENT_CANCEL';
    public const UNPUBLISH = 'EVENT_UNPUBLISH';
    public const NOTIFY = 'EVENT_NOTIFY';
    public const STATISTICS = 'EVENT_STATISTICS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Check if the attribute is one we support
        if (!in_array($attribute, [
            self::VIEW,
            self::EDIT,
            self::DELETE,
            self::PUBLISH,
            self::CANCEL,
            self::UNPUBLISH,
            self::NOTIFY,
            self::STATISTICS
        ])) {
            return false;
        }

        // Only vote on Event objects
        if (!$subject instanceof Event) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // User must be logged in for most operations
        if (!$user instanceof User) {
            // Only VIEW is allowed for anonymous users
            return $attribute === self::VIEW;
        }

        /** @var Event $event */
        $event = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($event, $user),
            self::EDIT => $this->canEdit($event, $user),
            self::DELETE => $this->canDelete($event, $user),
            self::PUBLISH => $this->canPublish($event, $user),
            self::CANCEL => $this->canCancel($event, $user),
            self::UNPUBLISH => $this->canUnpublish($event, $user),
            self::NOTIFY => $this->canNotify($event, $user),
            self::STATISTICS => $this->canViewStatistics($event, $user),
            default => false,
        };
    }

    private function canView(Event $event, ?User $user): bool
    {
        // Everyone can view published events
        if ($event->getStatus() === Event::STATUS_PUBLISHED) {
            return true;
        }

        // Anonymous users cannot view draft/cancelled events
        if (!$user instanceof User) {
            return false;
        }

        // Admins can view any event
        if ($this->isAdmin($user)) {
            return true;
        }

        // Organizers can view their own events in any status
        return $this->isOrganizer($event, $user);
    }

    private function canEdit(Event $event, User $user): bool
    {
        // Admins can edit any event
        if ($this->isAdmin($user)) {
            return true;
        }

        // Organizers can only edit their own events
        if (!$this->isOrganizer($event, $user)) {
            return false;
        }

        // Can only edit if event can be modified
        $ticketsSold = $this->calculationService->calculateTicketsSold($event);
        return $this->domainService->canBeModified($event, $ticketsSold);
    }

    private function canDelete(Event $event, User $user): bool
    {
        // Admins can delete any event
        if ($this->isAdmin($user)) {
            return true;
        }

        // Organizers can only delete their own events
        if (!$this->isOrganizer($event, $user)) {
            return false;
        }

        // Can only delete if no tickets have been sold
        $ticketsSold = $this->calculationService->calculateTicketsSold($event);
        return !$this->domainService->hasTicketsSold($ticketsSold);
    }

    private function canPublish(Event $event, User $user): bool
    {
        // Must be admin or organizer
        if (!$this->isAdmin($user) && !$this->isOrganizer($event, $user)) {
            return false;
        }

        // Event must be in publishable state
        return $this->domainService->canBePublished($event);
    }

    private function canCancel(Event $event, User $user): bool
    {
        // Admins can cancel any event
        if ($this->isAdmin($user)) {
            return true;
        }

        // Organizers can cancel their own events
        if (!$this->isOrganizer($event, $user)) {
            return false;
        }

        // Event must be cancellable
        return $this->domainService->canBeCancelled($event);
    }

    private function canUnpublish(Event $event, User $user): bool
    {
        // Admins can unpublish any event
        if ($this->isAdmin($user)) {
            return true;
        }

        // Organizers can unpublish their own events
        if (!$this->isOrganizer($event, $user)) {
            return false;
        }

        // Event must be unpublishable
        $ticketsSold = $this->calculationService->calculateTicketsSold($event);
        return $this->domainService->canBeUnpublished($event, $ticketsSold);
    }

    private function canNotify(Event $event, User $user): bool
    {
        // Admins can send notifications for any event
        if ($this->isAdmin($user)) {
            return true;
        }

        // Organizers can only notify for their own events
        if (!$this->isOrganizer($event, $user)) {
            return false;
        }

        // Can only notify for published events
        return $event->isPublished();
    }

    private function canViewStatistics(Event $event, User $user): bool
    {
        // Admins can view statistics for any event
        if ($this->isAdmin($user)) {
            return true;
        }

        // Organizers can only view statistics for their own events
        return $this->isOrganizer($event, $user);
    }

    private function isOrganizer(Event $event, User $user): bool
    {
        return $event->getOrganizer() === $user;
    }

    private function isAdmin(User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles(), true);
    }
}
