<?php

namespace App\Controller\Api;

use App\Service\TicketService;
use App\Service\ErrorHandlerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/tickets')]
class TicketController extends AbstractController
{
    public function __construct(
        private readonly TicketService       $ticketService,
        private readonly ErrorHandlerService $errorHandler,
    ) {}

    #[Route('/availability', name: 'api_tickets_availability', methods: ['GET'])]
    public function getAvailability(Request $request): JsonResponse
    {
        try {
            $eventId = $request->query->get('eventId');
            $ticketTypeId = $request->query->get('ticketTypeId');
            $quantity = (int) $request->query->get('quantity', 1);

            if (!$eventId) {
                return $this->json([
                    'error' => 'eventId parameter is required'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!$ticketTypeId) {
                return $this->json([
                    'error' => 'ticketTypeId parameter is required'
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($quantity < 1 || $quantity > 10) {
                return $this->json([
                    'error' => 'quantity must be between 1 and 10'
                ], Response::HTTP_BAD_REQUEST);
            }

            $availability = $this->ticketService->checkTicketAvailability($eventId, $ticketTypeId, $quantity);

            return $this->json($availability);
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Failed to check ticket availability');
        }
    }
}