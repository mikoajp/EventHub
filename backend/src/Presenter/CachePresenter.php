<?php

namespace App\Presenter;

final class CachePresenter
{
    public function presentStats(array $stats): array
    {
        return $stats;
    }

    public function presentResult(bool $result): array
    {
        return ['success' => $result];
    }

    public function presentMetrics(array $metrics): array
    {
        return $metrics;
    }
}
