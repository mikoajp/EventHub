<?php

namespace App\Exception\Idempotency;

class CommandAlreadyProcessingException extends IdempotencyException
{
    public function __construct(string $idempotencyKey, string $commandClass)
    {
        parent::__construct(
            sprintf(
                'Command "%s" with idempotency key "%s" is already being processed',
                $commandClass,
                $idempotencyKey
            ),
            409 // Conflict
        );
    }
}
