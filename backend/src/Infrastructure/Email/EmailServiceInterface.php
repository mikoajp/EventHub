<?php

namespace App\Infrastructure\Email;

use App\Entity\Event;
use App\Entity\Ticket;
use App\Entity\User;

interface EmailServiceInterface
{
    /**
     * Send ticket confirmation email
     */
    public function sendTicketConfirmation(Ticket $ticket): void;

    /**
     * Send event published notification
     */
    public function sendEventPublishedNotification(Event $event, User $subscriber): void;

    /**
     * Send event cancelled notification
     */
    public function sendEventCancelledNotification(Event $event, User $ticketHolder): void;

    /**
     * Send generic email
     */
    public function sendEmail(string $to, string $subject, string $template, array $context = []): void;
}