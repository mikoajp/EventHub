<?php

namespace App\Service;

use App\DTO\EventDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class ValidationService
{
    public function __construct(
        private ValidatorInterface $validator
    ) {}

    public function validateAndCreateEventDTO(Request $request): EventDTO
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            throw new \InvalidArgumentException('Invalid JSON', Response::HTTP_BAD_REQUEST);
        }

        $requiredFields = ['name', 'description', 'eventDate', 'venue', 'maxTickets'];
        $missingFields = array_diff($requiredFields, array_keys(array_filter($data)));

        if (!empty($missingFields)) {
            throw new \InvalidArgumentException(
                sprintf('Missing required fields: %s', implode(', ', $missingFields)),
                Response::HTTP_BAD_REQUEST
            );
        }

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

        $errors = $this->validator->validate($eventDTO);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            throw new \InvalidArgumentException(
                json_encode(['errors' => $errorMessages]),
                Response::HTTP_BAD_REQUEST
            );
        }

        return $eventDTO;
    }
}