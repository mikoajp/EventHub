<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Psr\Log\LoggerInterface;
use Throwable;

readonly class ErrorHandlerService
{
    public function __construct(
        #[Autowire('%kernel.environment%')]
        private string $environment,
        private LoggerInterface $logger
    ) {}

    public function createJsonResponse(Throwable $e, ?string $errorMessage = null): JsonResponse
    {
        $statusCode = $this->determineStatusCode($e);

        $this->logError($e, $errorMessage, $statusCode);

        $responseData = [
            'error' => $errorMessage ?? 'An error occurred',
            'message' => $e->getMessage()
        ];

        if ($this->environment === 'dev') {
            $responseData['trace'] = $e->getTraceAsString();
            $responseData['file'] = $e->getFile();
            $responseData['line'] = $e->getLine();
        }

        return new JsonResponse($responseData, $statusCode);
    }

    private function determineStatusCode(Throwable $e): int
    {
        if ($e->getCode() >= 400 && $e->getCode() < 600) {
            return $e->getCode();
        }

        return match (true) {
            $e instanceof AuthenticationException => Response::HTTP_UNAUTHORIZED,
            $e instanceof AccessDeniedException => Response::HTTP_FORBIDDEN,
            $e instanceof \InvalidArgumentException => Response::HTTP_BAD_REQUEST,
            $e instanceof \RuntimeException && str_contains($e->getMessage(), 'not authenticated') => Response::HTTP_UNAUTHORIZED,
            $e instanceof \RuntimeException && str_contains($e->getMessage(), 'Access denied') => Response::HTTP_FORBIDDEN,
            $e instanceof \RuntimeException && str_contains($e->getMessage(), 'not found') => Response::HTTP_NOT_FOUND,
            $e instanceof \DomainException => Response::HTTP_UNPROCESSABLE_ENTITY,
            default => Response::HTTP_INTERNAL_SERVER_ERROR
        };
    }

    private function logError(Throwable $e, ?string $errorMessage, int $statusCode): void
    {
        $context = [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'status_code' => $statusCode
        ];

        match (true) {
            $statusCode >= 500 => $this->logger->error($errorMessage ?? $e->getMessage(), $context),
            $statusCode >= 400 => $this->logger->warning($errorMessage ?? $e->getMessage(), $context),
            default => $this->logger->info($errorMessage ?? $e->getMessage(), $context)
        };
    }
}