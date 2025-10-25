<?php

namespace App\Presenter;

use App\DTO\CacheStatsDTO;

final class CachePresenter
{
    public function presentStats(array $stats): array
    {
        return $stats;
    }

    public function presentStatsDto(array $stats): CacheStatsDTO
    {
        return new CacheStatsDTO(stats: $stats);
    }

    public function presentResult(bool $result): array
    {
        return ['success' => $result];
    }

    public function presentMetrics(array $metrics): array
    {
        return $metrics;
    }

    public function presentMetricsDto(array $metrics): CacheStatsDTO
    {
        return new CacheStatsDTO(stats: $metrics);
    }
}
