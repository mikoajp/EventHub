<?php

namespace App\Security\Voter;

use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketStatus;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TicketVoter extends Voter
{
    public const VIEW = 'TICKET_VIEW';
    public const CANCEL = 'TICKET_CANCEL';
    public const DOWNLOAD = 'TICKET_DOWNLOAD';
    public const REFUND = 'TICKET_REFUND';
    public const TRANSFER = 'TICKET_TRANSFER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Check if the attribute is one we support
        if (!in_array($attribute, [
            self::VIEW,
            self::CANCEL,
            self::DOWNLOAD,
            self::REFUND,
            self::TRANSFER
        ])) {
            return false;
        }

        // Only vote on Ticket objects
        if (!$subject instanceof Ticket) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // User must be logged in to access tickets
        if (!$user instanceof User) {
            return false;
        }

        /** @var Ticket $ticket */
        $ticket = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($ticket, $user),
            self::CANCEL => $this->canCancel($ticket, $user),
            self::DOWNLOAD => $this->canDownload($ticket, $user),
            self::REFUND => $this->canRefund($ticket, $user),
            self::TRANSFER => $this->canTransfer($ticket, $user),
            default => false,
        };
    }

    private function canView(Ticket $ticket, User $user): bool
    {
        // Admins can view any ticket
        if ($this->isAdmin($user)) {
            return true;
        }

        // Event organizers can view tickets for their events
        if ($this->isEventOrganizer($ticket, $user)) {
            return true;
        }

        // Users can view their own tickets
        return $this->isTicketOwner($ticket, $user);
    }

    private function canDownload(Ticket $ticket, User $user): bool
    {
        // Same as view - only owners can download their tickets
        // Admins and organizers have read-only access
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($this->isEventOrganizer($ticket, $user)) {
            return true;
        }

        // Only ticket owner can download
        return $this->isTicketOwner($ticket, $user);
    }

    private function canCancel(Ticket $ticket, User $user): bool
    {
        // Admins can cancel any ticket
        if ($this->isAdmin($user)) {
            return true;
        }

        // Event organizers can cancel tickets for their events
        if ($this->isEventOrganizer($ticket, $user)) {
            return true;
        }

        // Users can only cancel their own tickets
        if (!$this->isTicketOwner($ticket, $user)) {
            return false;
        }

        // Cannot cancel if already cancelled or used
        if (in_array($ticket->getStatus(), [TicketStatus::CANCELLED, TicketStatus::USED, TicketStatus::REFUNDED], true)) {
            return false;
        }

        // Check if ticket is still cancellable (e.g., not too close to event)
        return $this->isTicketCancellable($ticket);
    }

    private function canRefund(Ticket $ticket, User $user): bool
    {
        // Only admins and event organizers can issue refunds
        if (!$this->isAdmin($user) && !$this->isEventOrganizer($ticket, $user)) {
            return false;
        }

        // Cannot refund if already refunded
        if ($ticket->getStatus() === TicketStatus::REFUNDED) {
            return false;
        }

        // Ticket must be in a refundable state
        return in_array($ticket->getStatus(), [TicketStatus::RESERVED, TicketStatus::PURCHASED, TicketStatus::CANCELLED], true);
    }

    private function canTransfer(Ticket $ticket, User $user): bool
    {
        // Admins can transfer any ticket
        if ($this->isAdmin($user)) {
            return true;
        }

        // Event organizers can transfer tickets for their events
        if ($this->isEventOrganizer($ticket, $user)) {
            return true;
        }

        // Users can only transfer their own tickets
        if (!$this->isTicketOwner($ticket, $user)) {
            return false;
        }

        // Ticket must not be used or cancelled
        if (in_array($ticket->getStatus(), [TicketStatus::USED, TicketStatus::CANCELLED, TicketStatus::REFUNDED], true)) {
            return false;
        }

        // Check if ticket is transferable
        return $this->isTicketTransferable($ticket);
    }

    private function isTicketOwner(Ticket $ticket, User $user): bool
    {
        return $ticket->getUser() === $user;
    }

    private function isEventOrganizer(Ticket $ticket, User $user): bool
    {
        return $ticket->getEvent()->getOrganizer() === $user;
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasRole(\App\Enum\UserRole::ADMIN);
    }

    private function isTicketCancellable(Ticket $ticket): bool
    {
        // Example: Can cancel up to 24 hours before event
        $event = $ticket->getEvent();
        $eventDate = $event->getEventDate();
        if (!$eventDate instanceof \DateTimeInterface) {
            return false;
        }
        $now = new \DateTimeImmutable();
        
        // Calculate hours until event
        $interval = $now->diff($eventDate);
        $hoursUntilEvent = ($interval->days * 24) + $interval->h;
        
        // Allow cancellation if more than 24 hours away
        return $hoursUntilEvent >= 24;
    }

    private function isTicketTransferable(Ticket $ticket): bool
    {
        // Example: Can transfer up to 1 hour before event
        $event = $ticket->getEvent();
        $eventDate = $event->getEventDate();
        if (!$eventDate instanceof \DateTimeInterface) {
            return false;
        }
        $now = new \DateTimeImmutable();
        
        // Calculate hours until event
        $interval = $now->diff($eventDate);
        $hoursUntilEvent = ($interval->days * 24) + $interval->h;
        
        // Allow transfer if more than 1 hour away
        return $hoursUntilEvent >= 1;
    }
}
