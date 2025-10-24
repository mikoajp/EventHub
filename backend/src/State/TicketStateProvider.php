<?php

namespace App\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Presenter\TicketPresenter;
use App\Repository\TicketRepository;

final class TicketStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly TicketRepository $repository,
        private readonly TicketPresenter $presenter,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        if ($operation instanceof Get) {
            $ticket = $this->repository->find($uriVariables['id'] ?? null);
            return $ticket ? $this->presenter->presentUserTickets([$ticket])['tickets'][0] : null;
        }
        if ($operation instanceof GetCollection) {
            $tickets = $this->repository->findAll();
            return $this->presenter->presentUserTickets($tickets);
        }
        return null;
    }
}
