<?php

namespace App\Contract\Presentation;

use App\Entity\Event;

interface EventPresenterInterface
{
    public function presentListItem(Event $event): array;
    public function presentDetails(Event $event): array;
}
