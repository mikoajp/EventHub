<?php

namespace App\DTO;

final readonly class UserResponseDTO
{
    public function __construct(
        public string $id,
        public string $email,
        public string $fullName,
        public string $createdAt,
    ) {}
}
