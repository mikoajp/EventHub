<?php
namespace App\Application\Command\Event;

use App\Entity\Event as EventEntity;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsMessageHandler]
final class PublishEventHandler
{
    public function __construct(
        private EventRepository $repo,
        private EntityManagerInterface $em,
        private TagAwareCacheInterface $cache,
    ) {}

    public function __invoke(PublishEventCommand $c): void
    {
        $event = $this->repo->find($c->id);
        if (!$event) { return; }
        if ($event->getStatus() === EventEntity::STATUS_DRAFT) {
            $event->setStatus(EventEntity::STATUS_PUBLISHED);
            $event->setPublishedAt(new \DateTimeImmutable());
            $this->em->flush();
            $this->cache->invalidateTags(['events', "event:{$c->id}", "event:stats:{$c->id}"]);
        }
    }
}
