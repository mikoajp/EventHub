<?php

namespace App\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Presenter\UserPresenter;
use App\Repository\UserRepository;

final class UserStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly UserPresenter $presenter,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        if ($operation instanceof Get) {
            $user = $this->repository->find($uriVariables['id'] ?? null);
            return $user ? $this->presenter->present($user) : null;
        }
        if ($operation instanceof GetCollection) {
            $users = $this->repository->findAll();
            return array_map(fn($u) => $this->presenter->present($u), $users);
        }
        return null;
    }
}
