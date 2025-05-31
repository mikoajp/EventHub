<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

readonly class ErrorHandlerService
{
    public function __construct(
        #[Autowire('%kernel.environment%')]
        private string $environment
    ) {}

    public function createJsonResponse(Throwable $e, string $errorMessage = null): JsonResponse
    {
        $statusCode = $this->determineStatusCode($e);

        $responseData = [
            'error' => $errorMessage ?? 'An error occurred',
            'message' => $e->getMessage()
        ];

        if ($this->environment === 'dev') {
            $responseData['trace'] = $e->getTraceAsString();
        }

        return new JsonResponse($responseData, $statusCode);
    }

    private function determineStatusCode(\Throwable $e): int
    {
        if ($e->getCode() >= 400 && $e->getCode() < 600) {
            return $e->getCode();
        }

        return match (true) {
            $e instanceof \InvalidArgumentException => Response::HTTP_BAD_REQUEST,
            $e instanceof \RuntimeException && str_contains($e->getMessage(), 'not authenticated') => Response::HTTP_UNAUTHORIZED,
            default => Response::HTTP_INTERNAL_SERVER_ERROR
        };
    }
}