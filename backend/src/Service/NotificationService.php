<?php

namespace App\Service;

use App\Entity\Event;
use App\Repository\UserRepository;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

final readonly class NotificationService
{
    public function __construct(
        private EmailService $emailService,
        private UserRepository $userRepository,
        private HubInterface $mercureHub
    ) {}

    public function notifyEventPublished(Event $event): void
    {
        $subscribers = $this->userRepository->findAll();

        foreach ($subscribers as $subscriber) {
            $this->emailService->sendEventPublishedNotification($event, $subscriber);
        }

        $update = new Update(
            'events/published',
            json_encode([
                'event_id' => $event->getId()->toString(),
                'event_name' => $event->getName(),
                'event_date' => $event->getEventDate()->format('Y-m-d H:i:s'),
                'message' => "New event published: {$event->getName()}"
            ])
        );

        $this->mercureHub->publish($update);
    }

    public function shareOnSocialMedia(Event $event): void
    {
        // Simulate social media sharing
        // In real app, this would integrate with Twitter, Facebook APIs
        
        $message = "🎉 New event: {$event->getName()} at {$event->getVenue()} on {$event->getEventDate()->format('M j, Y')}";
        
        // Log the social media post (simulate posting)
        error_log("Social Media Post: {$message}");
    }

    public function sendRealTimeUpdate(string $topic, array $data): void
    {
        $update = new Update($topic, json_encode($data));
        $this->mercureHub->publish($update);
    }
}