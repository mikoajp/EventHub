<?php
namespace App\Application\Command\Event;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateEventHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private TagAwareCacheInterface $cache,
    ) {}

    public function __invoke(CreateEventCommand $c): string
    {
        return $this->em->wrapInTransaction(function() use ($c) {
            // TODO: create and persist Event aggregate
            $eventId = (string) \Symfony\Component\Uid\Uuid::v4();
            $this->cache->invalidateTags(['events']);
            return $eventId;
        });
    }
}
