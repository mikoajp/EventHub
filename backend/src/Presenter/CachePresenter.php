<?php

namespace App\Presenter;

use App\Contract\Presentation\CachePresenterInterface;

final class CachePresenter implements \App\Contract\Presentation\CachePresenterInterface
{
    public function presentStats(array $stats): array { return $stats; }
    public function presentResult(array $result): array { return $result; }
    public function presentMetrics(array $metrics): array { return $metrics; }
}
