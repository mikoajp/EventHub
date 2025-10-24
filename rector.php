<?php
declare(strict_types=1);

use Rector\Config\RectorConfig;

return static function (RectorConfig ): void {
    ->paths([__DIR__ . '/backend/src']);
};
