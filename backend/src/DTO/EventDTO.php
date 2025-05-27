<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class EventDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 2000)]
    public string $description;

    #[Assert\NotBlank]
    #[Assert\DateTime]
    public \DateTimeImmutable $eventDate;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    public string $venue;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $maxTickets;

    public function __construct(
        string $name,
        string $description,
        \DateTimeImmutable $eventDate,
        string $venue,
        int $maxTickets
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->eventDate = $eventDate;
        $this->venue = $venue;
        $this->maxTickets = $maxTickets;
    }
}