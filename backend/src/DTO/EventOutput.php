<?php

namespace App\DTO;

final class EventOutput
{
    public string $id;
    public string $name;
    public string $description;
    public string $eventDate;
    public string $venue;
    public int $maxTickets;
    public string $status;
    public ?string $publishedAt = null;
    public string $createdAt;
    public int $ticketsSold;
    public int $availableTickets;
}
