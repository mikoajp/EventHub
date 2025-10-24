<?php

namespace App\Presenter;

use App\Contract\Presentation\UserPresenterInterface;

use App\DTO\UserOutput;
use App\Entity\User;

final class UserPresenter implements \App\Contract\Presentation\UserPresenterInterface
{
    public function present(User $user): UserOutput
    {
        $out = new UserOutput();
        $out->id = $user->getId()->toRfc4122();
        $out->email = $user->getEmail();
        $out->fullName = $user->getFullName();
        $out->createdAt = $user->getCreatedAt()->format('c');
        return $out;
    }

    public function presentLoginResponse(array $data): array
    {
        return [
            'token' => $data['token'] ?? null,
            'user' => [
                'id' => $data['user']['id'] ?? null,
                'email' => $data['user']['email'] ?? null,
                'createdAt' => $data['user']['createdAt'] ?? null,
            ],
        ];
    }

    public function presentProfile(array $data): array
    {
        return [
            'id' => $data['id'] ?? null,
            'email' => $data['email'] ?? null,
            'fullName' => $data['fullName'] ?? null,
            'createdAt' => $data['createdAt'] ?? null,
            'phone' => $data['phone'] ?? null,
        ];
    }

    public function presentRegistrationResponse(array $data): array
    {
        return [
            'id' => $data['id'] ?? null,
            'email' => $data['email'] ?? null,
            'status' => $data['status'] ?? 'registered',
        ];
    }
}
