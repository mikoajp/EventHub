<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UserRegistrationDTO
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 128)]
    public string $password;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    public string $firstName;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    public string $lastName;

    #[Assert\Length(min: 7, max: 30)]
    public ?string $phone;

    public bool $wantToBeOrganizer;

    public function __construct(
        string $email, 
        string $password, 
        string $firstName, 
        string $lastName, 
        ?string $phone = null,
        bool $wantToBeOrganizer = false
    )
    {
        $this->email = $email;
        $this->password = $password;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->phone = $phone;
        $this->wantToBeOrganizer = $wantToBeOrganizer;
    }
}
