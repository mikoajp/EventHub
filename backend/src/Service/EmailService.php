<?php

namespace App\Service;

use App\Entity\Ticket;
use App\Infrastructure\Email\EmailServiceInterface;

final class EmailService
{
    public function __construct(private EmailServiceInterface $mailer) {}

    public function sendTicketConfirmation(Ticket $ticket): void
    {
        $this->mailer->sendTicketConfirmation($ticket);
    }
}
