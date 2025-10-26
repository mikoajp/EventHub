<?php

namespace App\Exception\Validation;

use App\Exception\ApplicationException;

/**
 * Thrown when validation fails.
 */
final class ValidationException extends ApplicationException
{
    protected string $errorCode = 'VALIDATION_FAILED';
    
    private array $violations;

    public function __construct(array $violations)
    {
        $this->violations = $violations;
        
        $message = 'Validation failed';
        if (!empty($violations)) {
            $message .= ': ' . implode(', ', array_map(
                fn($field, $error) => "$field: $error",
                array_keys($violations),
                $violations
            ));
        }
        
        parent::__construct($message, 0, null, ['violations' => $violations]);
    }

    public function getViolations(): array
    {
        return $this->violations;
    }

    public function toArray(): array
    {
        return [
            'error' => [
                'code' => $this->getErrorCode(),
                'message' => 'Validation failed',
                'violations' => $this->violations
            ]
        ];
    }
}
