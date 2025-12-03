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
        $eventId = $request->query->get('eventId');
        $ticketTypeId = $request->query->get('ticketTypeId');
        $quantity = (int) $request->query->get('quantity', 1);

        $violations = [];
        if (!$eventId) { $violations['eventId'] = 'eventId is required'; }
        if (!$ticketTypeId) { $violations['ticketTypeId'] = 'ticketTypeId is required'; }
        if ($quantity < 1 || $quantity > 10) { $violations['quantity'] = 'quantity must be between 1 and 10'; }
        if ($violations) { throw new \App\Exception\Validation\ValidationException($violations); }

        $envelope = $this->queryBus->dispatch(new CheckTicketAvailabilityQuery($eventId, $ticketTypeId, $quantity));
        $availability = $envelope->last(HandledStamp::class)->getResult();
        
        return $this->json($this->ticketPresenter->presentAvailability($availability));
    }

    #[Route('/purchase', name: 'api_tickets_purchase', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function purchase(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \App\Exception\Validation\ValidationException(['json' => 'Invalid JSON']);
        }
        $eventId = $data['eventId'] ?? null;
        $ticketTypeId = $data['ticketTypeId'] ?? null;
        if (!$eventId || !$ticketTypeId) {
            throw new \App\Exception\Validation\ValidationException(['eventId' => 'required', 'ticketTypeId' => 'required']);
        }
        $idempotencyKey = $request->headers->get('X-Idempotency-Key') ?? ($data['idempotencyKey'] ?? throw new \App\Exception\Validation\ValidationException(['idempotencyKey' => 'X-Idempotency-Key header required']));
        $paymentMethodId = $data['paymentMethodId'] ?? 'pm_test_card';
        $quantity = (int) ($data['quantity'] ?? 1);
        
        // Validate quantity range
        if ($quantity < 1 || $quantity > 10) {
            throw new \App\Exception\Validation\ValidationException([
                'quantity' => 'Quantity must be between 1 and 10'
            ]);
        }

        $envelope = $this->commandBus->dispatch(new PurchaseTicketCommand(
            $eventId,
            $ticketTypeId,
            $quantity,
            $user->getId()->toString(),
            $paymentMethodId,
            $idempotencyKey
        ));
        $ticketIds = $envelope->last(HandledStamp::class)->getResult();
        
        return $this->json($this->ticketPresenter->presentPurchase(['ticket_ids' => $ticketIds]), Response::HTTP_CREATED);
    }

    #[Route('/user/current', name: 'api_tickets_my', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function my(#[CurrentUser] User $user): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetUserTicketsQuery($user->getId()->toString()));
        $tickets = $envelope->last(HandledStamp::class)->getResult();
        
        return $this->json($this->ticketPresenter->presentUserTickets($tickets));
    }

    #[Route('/{id}/cancel', name: 'api_tickets_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function cancel(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \App\Exception\Validation\ValidationException(['json' => 'Invalid JSON']);
        }
        $reason = $data['reason'] ?? null;
        
        $this->commandBus->dispatch(new CancelTicketCommand($id, $user->getId()->toString(), $reason));
        
        return $this->json($this->ticketPresenter->presentCancel());
    }
}
