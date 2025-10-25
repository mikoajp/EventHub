<?php

namespace App\Contract\Presentation;

use App\Entity\Event;

interface EventPresenterInterface
{
    public function presentListItem(Event $event): array;
    public function presentDetails(Event $event): array;

    public function presentListItemDto(Event $event): \App\DTO\EventListItemDTO;
    public function presentDetailsDto(Event $event): \App\DTO\EventDetailsDTO;
}
