<?php

namespace App\Infrastructure\Validation;

use App\DTO\EventDTO;
use Symfony\Component\HttpFoundation\Request;

interface RequestValidatorInterface
{
    /**
     * Validate and extract data from request
     */
    public function validateRequest(Request $request, array $rules): array;

    /**
     * Validate specific DTO
     */
    public function validateDTO(object $dto): array;

    /**
     * Backwards-compat alias for validateDTO
     */
    public function validate(object $dto): array;

    /**
     * Check if request has valid JSON
     */
    public function hasValidJson(Request $request): bool;

    /**
     * Extract and validate JSON data
     */
    public function extractJsonData(Request $request): array;

    /**
     * Validate payload and create EventDTO
     */
    public function validateAndCreateEventDTO(Request $request): EventDTO;
}