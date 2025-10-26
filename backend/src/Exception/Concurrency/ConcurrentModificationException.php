<?php

namespace App\Exception\Concurrency;

use App\Exception\ApplicationException;

/**
 * Thrown when a concurrent modification is detected.
 */
final class ConcurrentModificationException extends ApplicationException
{
    protected string $errorCode = 'CONCURRENT_MODIFICATION';

    public function __construct(string $resource, string $resourceId)
    {
        parent::__construct(
            sprintf('%s "%s" is being modified by another request. Please try again.', $resource, $resourceId),
            0,
            null,
            [
                'resource' => $resource,
                'resource_id' => $resourceId
            ]
        );
    }
}
