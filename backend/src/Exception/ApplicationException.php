<?php

namespace App\Exception;

/**
 * Base exception for application-level errors.
 * These exceptions represent application flow errors.
 */
abstract class ApplicationException extends \RuntimeException
{
    protected string $errorCode;
    protected array $context = [];

    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function toArray(): array
    {
        return [
            'error' => [
                'code' => $this->getErrorCode(),
                'message' => $this->getMessage(),
                'context' => $this->getContext()
            ]
        ];
    }
}
