<?php

namespace App\Contract\Presentation;

interface CachePresenterInterface
{
    public function presentStats(array $stats): array;
    public function presentResult(array $result): array;
    public function presentMetrics(array $metrics): array;
}
