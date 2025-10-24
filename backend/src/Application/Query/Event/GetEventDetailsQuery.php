<?php
namespace App\Application\Query\Event;

final class GetEventDetailsQuery
{
    public function __construct(public string $id) {}
}
