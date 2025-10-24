<?php
namespace App\Application\Command\Event;

final class PublishEventCommand
{
    public function __construct(public string $id, public string $userId) {}
}
