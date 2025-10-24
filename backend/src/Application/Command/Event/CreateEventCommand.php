<?php
namespace App\Application\Command\Event;

final class CreateEventCommand
{
    public function __construct(
        public string $name,
        public \DateTimeImmutable $startsAt
    ) {}
}
