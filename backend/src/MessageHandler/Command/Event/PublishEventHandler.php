<?php

namespace App\MessageHandler\Command\Event;

use App\Entity\Event;
use App\Message\Command\Event\PublishEventCommand;
use App\Message\Event\EventPublishedEvent;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class PublishEventHandler
{
    public function __construct(
        private EventRepository $eventRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $eventBus
    ) {}

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(PublishEventCommand $command): void
    {
        $event = $this->eventRepository->find(Uuid::fromString($command->eventId));
        
        if (!$event) {
            throw new \InvalidArgumentException('Event not found');
        }

        if ($event->getStatus() !== Event::STATUS_DRAFT) {
            throw new \InvalidArgumentException('Only draft events can be published');
        }

        $event->setStatus(Event::STATUS_PUBLISHED);
        $this->entityManager->flush();

        $this->eventBus->dispatch(new EventPublishedEvent(
            $event->getId()->toString(),
            new \DateTimeImmutable()
        ));
    }
}