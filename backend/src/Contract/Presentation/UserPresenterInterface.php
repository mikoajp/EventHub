<?php

namespace App\Contract\Presentation;

interface UserPresenterInterface
{
    public function presentLoginResponse(array $data): array;
    public function presentProfile(array $data): array;
    public function presentRegistrationResponse(array $data): array;
}
