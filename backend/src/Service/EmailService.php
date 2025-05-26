<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Ticket;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

final readonly class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $fromEmail = 'noreply@eventhub.com'
    ) {}

    public function sendTicketConfirmation(Ticket $ticket): void
    {
        $user = $ticket->getUser();
        $event = $ticket->getEvent();

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject("Potwierdzenie zakupu biletu - {$event->getName()}")
            ->html($this->twig->render('emails/ticket_confirmation.html.twig', [
                'user' => $user,
                'ticket' => $ticket,
                'event' => $event
            ]));

        $this->mailer->send($email);
    }

    public function sendEventPublishedNotification(Event $event, User $subscriber): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($subscriber->getEmail())
            ->subject("Nowy event: {$event->getName()}")
            ->html($this->twig->render('emails/event_published.html.twig', [
                'user' => $subscriber,
                'event' => $event
            ]));

        $this->mailer->send($email);
    }

    public function sendEventCancelledNotification(Event $event, User $ticketHolder): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($ticketHolder->getEmail())
            ->subject("Event odwołany: {$event->getName()}")
            ->html($this->twig->render('emails/event_cancelled.html.twig', [
                'user' => $ticketHolder,
                'event' => $event
            ]));

        $this->mailer->send($email);
    }
}