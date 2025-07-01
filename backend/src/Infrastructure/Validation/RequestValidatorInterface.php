<?php

namespace App\Infrastructure\Validation;

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
     * Check if request has valid JSON
     */
    public function hasValidJson(Request $request): bool;

    /**
     * Extract and validate JSON data
     */
    public function extractJsonData(Request $request): array;
}