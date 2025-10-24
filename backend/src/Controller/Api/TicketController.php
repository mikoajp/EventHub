<?php

namespace App\Controller\Api;

use App\Application\Service\TicketApplicationService;
use App\Repository\EventRepository;
use App\Repository\TicketTypeRepository;
use App\Entity\User;
use App\Service\ErrorHandlerService;
use App\Presenter\TicketPresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/tickets')]
class TicketController extends AbstractController
{
    public function __construct(
        private readonly TicketApplicationService $ticketApplicationService,
        private readonly EventRepository $eventRepository,
        private readonly TicketTypeRepository $ticketTypeRepository,
        private readonly ErrorHandlerService $errorHandler,
        private readonly TicketPresenter $ticketPresenter,
    ) {}

    #[Route('/availability', name: 'api_tickets_availability', methods: ['GET'])]
    public function getAvailability(Request $request): JsonResponse
    {
        try {
            $eventId = $request->query->get('eventId');
            $ticketTypeId = $request->query->get('ticketTypeId');
            $quantity = (int) $request->query->get('quantity', 1);

            if (!$eventId) {
                return $this->json(['error' => 'eventId parameter is required'], Response::HTTP_BAD_REQUEST);
            }
            if (!$ticketTypeId) {
                return $this->json(['error' => 'ticketTypeId parameter is required'], Response::HTTP_BAD_REQUEST);
            }
            if ($quantity < 1 || $quantity > 10) {
                return $this->json(['error' => 'quantity must be between 1 and 10'], Response::HTTP_BAD_REQUEST);
            }

            $availability = $this->ticketApplicationService->checkTicketAvailability($eventId, $ticketTypeId, $quantity);
            return $this->json($this->ticketPresenter->presentAvailability($availability));
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Failed to check ticket availability');
        }
    }

    #[Route('/purchase', name: 'api_tickets_purchase', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function purchase(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        try {
            if (!$user) {
                return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
            }
            $data = json_decode($request->getContent(), true) ?? [];
            $eventId = $data['eventId'] ?? null;
            $ticketTypeId = $data['ticketTypeId'] ?? null;
            if (!$eventId || !$ticketTypeId) {
                return $this->json(['error' => 'eventId and ticketTypeId are required'], Response::HTTP_BAD_REQUEST);
            }
            $result = $this->ticketApplicationService->purchaseTicketByIds($user, $eventId, $ticketTypeId);
            return $this->json($this->ticketPresenter->presentPurchase($result), Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Failed to purchase ticket');
        }
    }

    #[Route('/my', name: 'api_tickets_my', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function my(#[CurrentUser] ?User $user): JsonResponse
    {
        try {
            if (!$user) {
                return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
            }
            $tickets = $this->ticketApplicationService->getUserTickets($user);
            return $this->json($this->ticketPresenter->presentUserTickets($tickets));
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Failed to fetch user tickets');
        }
    }

    #[Route('/{id}/cancel', name: 'api_tickets_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function cancel(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        try {
            if (!$user) {
                return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
            }
            $data = json_decode($request->getContent(), true) ?? [];
            $reason = $data['reason'] ?? null;
            $this->ticketApplicationService->cancelTicket($id, $user, $reason);
            return $this->json($this->ticketPresenter->presentCancel());
        } catch (\Exception $e) {
            return $this->errorHandler->createJsonResponse($e, 'Failed to cancel ticket');
        }
    }
}
