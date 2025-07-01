<?php

namespace App\Infrastructure\Email;

use App\Entity\Event;
use App\Entity\Ticket;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

final readonly class SymfonyMailerAdapter implements EmailServiceInterface
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

        $this->sendEmail(
            $user->getEmail(),
            "Potwierdzenie zakupu biletu - {$event->getName()}",
            'emails/ticket_confirmation.html.twig',
            [
                'user' => $user,
                'ticket' => $ticket,
                'event' => $event
            ]
        );
    }

    public function sendEventPublishedNotification(Event $event, User $subscriber): void
    {
        $this->sendEmail(
            $subscriber->getEmail(),
            "Nowy event: {$event->getName()}",
            'emails/event_published.html.twig',
            [
                'user' => $subscriber,
                'event' => $event
            ]
        );
    }

    public function sendEventCancelledNotification(Event $event, User $ticketHolder): void
    {
        $this->sendEmail(
            $ticketHolder->getEmail(),
            "Event odwołany: {$event->getName()}",
            'emails/event_cancelled.html.twig',
            [
                'user' => $ticketHolder,
                'event' => $event
            ]
        );
    }

    public function sendEmail(string $to, string $subject, string $template, array $context = []): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($to)
            ->subject($subject)
            ->html($this->twig->render($template, $context));

        $this->mailer->send($email);
    }
}