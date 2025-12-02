<?php

namespace App\Presenter;

use App\Contract\Presentation\UserPresenterInterface;
use App\DTO\UserResponseDTO;
use App\Entity\User;

final class UserPresenter implements UserPresenterInterface
{
    public function present(User $user): UserResponseDTO
    {
        return new UserResponseDTO(
            id: $user->getId()->toRfc4122(),
            email: $user->getEmail(),
            fullName: $user->getFullName(),
            createdAt: $user->getCreatedAt()->format('c'),
        );
    }

    public function presentLoginResponse(array $data): array
    {
        return [
            'token' => $data['token'] ?? null,
            'user' => [
                'id' => $data['user']['id'] ?? null,
                'email' => $data['user']['email'] ?? null,
                'firstName' => $data['user']['firstName'] ?? null,
                'createdAt' => $data['user']['createdAt'] ?? null,
                'roles' => $data['user']['roles'] ?? [],
            ],
        ];
    }

    public function presentProfile(array $data): array
    {
        return [
            'id' => $data['id'] ?? null,
            'email' => $data['email'] ?? null,
            'firstName' => $data['firstName'] ?? null,
            'lastName' => $data['lastName'] ?? null,
            'fullName' => $data['fullName'] ?? null,
            'phone' => $data['phone'] ?? null,
            'createdAt' => $data['createdAt'] ?? null,
            'roles' => $data['roles'] ?? [],
        ];
    }

    public function presentRegistrationResponse(array $data): array
    {
        return [
            'token' => $data['token'] ?? null,
            'user' => [
                'id' => $data['user']['id'] ?? null,
                'email' => $data['user']['email'] ?? null,
                'firstName' => $data['user']['firstName'] ?? null,
                'createdAt' => $data['user']['createdAt'] ?? null,
                'roles' => $data['user']['roles'] ?? [],
            ],
        ];
    }
}
