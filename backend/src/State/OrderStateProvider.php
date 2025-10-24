<?php

namespace App\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Contract\Presentation\OrderPresenterInterface;
use App\Repository\OrderRepository;

final class OrderStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly OrderRepository $repository,
        private readonly OrderPresenterInterface $presenter,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        if ($operation instanceof Get) {
            $order = $this->repository->find($uriVariables['id'] ?? null);
            return $order ? $this->presenter->presentDetails($order) : null;
        }
        if ($operation instanceof GetCollection) {
            $orders = $this->repository->findAll();
            return array_map(fn($o) => $this->presenter->presentSummary($o), $orders);
        }
        return null;
    }
}
