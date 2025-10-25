<?php

namespace App\DTO;

final readonly class CacheStatsDTO
{
    /** @param array<string, mixed> $stats */
    public function __construct(
        public array $stats = [],
    ) {}
}
