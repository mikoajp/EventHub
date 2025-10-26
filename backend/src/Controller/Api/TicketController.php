<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Message\Command\Ticket\CancelTicketCommand;
use App\Message\Command\Ticket\PurchaseTicketCommand;
use App\Message\Query\Ticket\CheckTicketAvailabilityQuery;
use App\Message\Query\Ticket\GetUserTicketsQuery;
use App\Presenter\TicketPresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/tickets')]
class TicketController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'messenger.bus.command')] private readonly MessageBusInterface $commandBus,
        #[Autowire(service: 'messenger.bus.query')] private readonly MessageBusInterface $queryBus,
        private readonly TicketPresenter $ticketPresenter
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

            $envelope = $this->queryBus->dispatch(new CheckTicketAvailabilityQuery($eventId, $ticketTypeId, $quantity));
            $availability = $envelope->last(HandledStamp::class)->getResult();
            
            return $this->json($this->ticketPresenter->presentAvailability($availability));
        } catch (\Exception $e) {
            $status = ($e->getCode() >= 400 && $e->getCode() <= 599) ? $e->getCode() : 400;
            return $this->json(['error' => 'Failed to check ticket availability', 'message' => $e->getMessage()], $status);
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
            // Prefer header X-Idempotency-Key, then payload, else fallback
            $idempotencyKey = $request->headers->get('X-Idempotency-Key') ?? ($data['idempotencyKey'] ?? uniqid('ticket_purchase_', true));
            $paymentMethodId = $data['paymentMethodId'] ?? 'pm_test_card';
            $quantity = $data['quantity'] ?? 1;

            $envelope = $this->commandBus->dispatch(new PurchaseTicketCommand(
                $eventId,
                $ticketTypeId,
                (int) $quantity,
                $user->getId()->toString(),
                $paymentMethodId,
                $idempotencyKey
            ));
            $ticketIds = $envelope->last(HandledStamp::class)->getResult();
            
            $result = [
                'ticket_ids' => $ticketIds,
                'status' => 'reserved',
                'message' => 'Ticket purchase initiated'
            ];
            
            return $this->json($this->ticketPresenter->presentPurchase($result), Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $status = ($e->getCode() >= 400 && $e->getCode() <= 599) ? $e->getCode() : 400;
            return $this->json(['error' => 'Failed to purchase ticket', 'message' => $e->getMessage()], $status);
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
            $envelope = $this->queryBus->dispatch(new GetUserTicketsQuery($user->getId()->toString()));
            $tickets = $envelope->last(HandledStamp::class)->getResult();
            
            return $this->json($this->ticketPresenter->presentUserTickets($tickets));
        } catch (\Exception $e) {
            $status = ($e->getCode() >= 400 && $e->getCode() <= 599) ? $e->getCode() : 400;
            return $this->json(['error' => 'Failed to fetch user tickets', 'message' => $e->getMessage()], $status);
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
            
            $this->commandBus->dispatch(new CancelTicketCommand($id, $user->getId()->toString(), $reason));
            
            return $this->json($this->ticketPresenter->presentCancel());
        } catch (\Exception $e) {
            $status = ($e->getCode() >= 400 && $e->getCode() <= 599) ? $e->getCode() : 400;
            return $this->json(['error' => 'Failed to cancel ticket', 'message' => $e->getMessage()], $status);
        }
    }
}
