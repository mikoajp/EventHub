<?php

namespace App\Infrastructure\Validation;

use App\DTO\EventDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class SymfonyRequestValidator implements RequestValidatorInterface
{
    public function __construct(
        private ValidatorInterface $validator
    ) {}

    public function validateRequest(Request $request, array $rules): array
    {
        $data = $this->extractJsonData($request);

        if (isset($rules['required'])) {
            $missingFields = array_diff($rules['required'], array_keys(array_filter($data)));
            
            if (!empty($missingFields)) {
                throw new \InvalidArgumentException(
                    sprintf('Missing required fields: %s', implode(', ', $missingFields)),
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        if (isset($rules['types'])) {
            foreach ($rules['types'] as $field => $expectedType) {
                if (isset($data[$field]) && !$this->validateFieldType($data[$field], $expectedType)) {
                    throw new \InvalidArgumentException(
                        sprintf('Field "%s" must be of type %s', $field, $expectedType),
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }
        }

        return $data;
    }

    public function validateDTO(object $dto): array
    {
        $errors = $this->validator->validate($dto);
        
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $errorMessages;
        }

        return [];
    }

    public function hasValidJson(Request $request): bool
    {
        $content = $request->getContent();
        if (empty($content)) {
            return false;
        }

        json_decode($content, true);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function extractJsonData(Request $request): array
    {
        if (!$this->hasValidJson($request)) {
            throw new \InvalidArgumentException('Invalid JSON', Response::HTTP_BAD_REQUEST);
        }

        return json_decode($request->getContent(), true) ?? [];
    }

    public function validateAndCreateEventDTO(Request $request): EventDTO
    {
        $data = $this->validateRequest($request, [
            'required' => ['name', 'description', 'eventDate', 'venue', 'maxTickets'],
            'types' => [
                'name' => 'string',
                'description' => 'string',
                'eventDate' => 'string',
                'venue' => 'string',
                'maxTickets' => 'integer'
            ]
        ]);

        try {
            $eventDate = new \DateTimeImmutable($data['eventDate']);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                'Invalid date format. Please provide eventDate in ISO 8601 format (e.g., 2025-07-17T15:30:00Z)',
                Response::HTTP_BAD_REQUEST
            );
        }

        $eventDTO = new EventDTO(
            trim($data['name']),
            trim($data['description']),
            $eventDate,
            trim($data['venue']),
            (int)$data['maxTickets']
        );

        $validationErrors = $this->validateDTO($eventDTO);
        if (!empty($validationErrors)) {
            throw new \InvalidArgumentException(
                json_encode(['errors' => $validationErrors]),
                Response::HTTP_BAD_REQUEST
            );
        }

        return $eventDTO;
    }

    private function validateFieldType(mixed $value, string $expectedType): bool
    {
        return match($expectedType) {
            'string' => is_string($value),
            'integer' => is_int($value) || (is_string($value) && ctype_digit($value)),
            'float' => is_float($value) || is_numeric($value),
            'boolean' => is_bool($value),
            'array' => is_array($value),
            default => true
        };
    }
}