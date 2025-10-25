<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class UserRegistrationDTO
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 128)]
    public string $password;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public string $fullName;

    public function __construct(string $email, string $password, string $fullName)
    {
        $this->email = $email;
        $this->password = $password;
        $this->fullName = $fullName;
    }
}
