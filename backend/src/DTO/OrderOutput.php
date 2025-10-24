<?php

namespace App\DTO;

final class OrderOutput
{
    public string $id;
    public string $status;
    public int $totalAmount;
    public string $totalAmountFormatted;
    public string $createdAt;
    public ?string $updatedAt = null;
    /** @var array<int, array<string, mixed>> */
    public array $items = [];
}
