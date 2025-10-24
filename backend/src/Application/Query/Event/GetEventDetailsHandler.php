<?php
namespace App\Application\Query\Event;

use App\Presenter\EventPresenter;
use App\Repository\EventRepository;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetEventDetailsHandler
{
    public function __construct(
        private EventRepository $repo,
        private EventPresenter $presenter,
        private TagAwareCacheInterface $cache,
    ) {}

    public function __invoke(GetEventDetailsQuery $q): array
    {
        $key = "event:{$q->id}";
        return $this->cache->get($key, function(ItemInterface $item) use ($q) {
            $item->expiresAfter(600);
            $item->tag(['events', "event:{$q->id}"]);
            $event = $this->repo->find($q->id);
            return $event ? $this->presenter->presentDetails($event) : [];
        });
    }
}
