<?php

namespace App\Presenter;

use App\DTO\UserOutput;
use App\Entity\User;

final class UserPresenter
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
}
