<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Event;
use App\Presenter\EventPresenter;
use App\Repository\EventRepository;

final class EventStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly EventRepository $repository,
        private readonly EventPresenter $presenter,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        if ($operation instanceof Get) {
            $event = $this->repository->find($uriVariables['id'] ?? null);
            return $event ? $this->presenter->present($event) : null;
        }
        if ($operation instanceof GetCollection) {
            $events = $this->repository->findPublishedEvents();
            return array_map(fn(Event $e) => $this->presenter->present($e), $events);
        }
        return null;
    }
}
