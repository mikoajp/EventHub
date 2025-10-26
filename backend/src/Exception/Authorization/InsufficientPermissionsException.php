<?php

namespace App\Exception\Authorization;

use App\Exception\ApplicationException;

/**
 * Thrown when a user doesn't have permission to perform an action.
 */
final class InsufficientPermissionsException extends ApplicationException
{
    protected string $errorCode = 'INSUFFICIENT_PERMISSIONS';

    public function __construct(string $action, ?string $resource = null)
    {
        $message = $resource 
            ? sprintf('You do not have permission to %s on resource "%s"', $action, $resource)
            : sprintf('You do not have permission to %s', $action);
            
        parent::__construct($message, 0, null, [
            'action' => $action,
            'resource' => $resource
        ]);
    }
}
