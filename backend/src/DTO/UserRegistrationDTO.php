<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UserRegistrationDTO
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    public string $password;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    public string $firstName;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    public string $lastName;

    #[Assert\Length(min: 9, max: 15)]
    public ?string $phone;

    public function __construct(
        string $email,
        string $password,
        string $firstName,
        string $lastName,
        ?string $phone = null
    ) {
        $this->email = trim($email);
        $this->password = $password;
        $this->firstName = trim($firstName);
        $this->lastName = trim($lastName);
        $this->phone = $phone ? trim($phone) : null;
    }
}