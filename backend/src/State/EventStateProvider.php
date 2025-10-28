<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Event;
use App\Presenter\EventPresenter;
use App\Repository\EventRepository;
use Symfony\Component\HttpFoundation\RequestStack;

final class EventStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly EventRepository $repository,
        private readonly EventPresenter $presenter,
        private readonly RequestStack $requestStack,
    ) {}

    /**
     * Provide event data for API Platform operations.
     *
     * @return iterable<object>|object|array<string, mixed>|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        if ($operation instanceof Get) {
            $event = $this->repository->find($uriVariables['id'] ?? null);

            if (!$event) {
                return null;
            }

            return $this->presenter->presentDetails($event);
        }

        if ($operation instanceof GetCollection) {
            $request = $this->requestStack->getCurrentRequest();
            $searchTerm = $request?->query->get('search');

            if ($searchTerm) {
                $events = $this->repository->searchPublishedEvents($searchTerm);
            } else {
                $events = $this->repository->findPublishedEvents();
            }

            $presented = array_map(
                fn(Event $e) => $this->presenter->presentListItem($e),
                $events
            );

            return [
                'events' => $presented,
                'pagination' => [
                    'total' => count($presented),
                    'page' => 1,
                    'limit' => count($presented),
                ]
            ];
        }

        return null;
    }
}