<?php

namespace App\Presenter;

use App\Contract\Presentation\TicketPresenterInterface;
use App\Domain\ValueObject\Money;
use App\Entity\Ticket;
use App\DTO\TicketAvailabilityDTO;
use App\DTO\TicketPurchaseResultDTO;
use App\DTO\UserTicketsDTO;

final class TicketPresenter implements TicketPresenterInterface
{
    public function presentAvailability(array $availability): array
    {
        return $availability;
    }

    public function presentAvailabilityDto(array $availability): TicketAvailabilityDTO
    {
        return new TicketAvailabilityDTO(
            eventId: (string)($availability['eventId'] ?? ''),
            ticketTypeId: (string)($availability['ticketTypeId'] ?? ''),
            requestedQuantity: (int)($availability['requestedQuantity'] ?? 0),
            available: (bool)($availability['available'] ?? false),
            availableQuantity: (int)($availability['availableQuantity'] ?? 0),
        );
    }

    public function presentPurchase(array $result): array
    {
        // normalize price fields when present
        if (isset($result['price']) && is_int($result['price'])) {
            $result['priceFormatted'] = Money::fromInt($result['price'])->format();
        }
        if (isset($result['total']) && is_int($result['total'])) {
            $result['totalFormatted'] = Money::fromInt($result['total'])->format();
        }
        return $result;
    }

    public function presentPurchaseDto(array $result): TicketPurchaseResultDTO
    {
        $total = (int)($result['total'] ?? 0);
        $totalFormatted = Money::fromInt($total)->format();
        /** @var array<int, array{id:string, ticketType:string, price:int}> $items */
        $items = (array)($result['items'] ?? []);
        return new TicketPurchaseResultDTO(
            orderId: (string)($result['orderId'] ?? ''),
            paymentId: (string)($result['paymentId'] ?? ''),
            total: $total,
            totalFormatted: $totalFormatted,
            items: $items,
        );
    }

    public function presentUserTickets(array $tickets): array
    {
        $map = function ($t): array {
            if ($t instanceof Ticket) {
                $event = $t->getEvent();
                $ticketType = $t->getTicketType();
                
                return [
                    'id' => $t->getId()->toRfc4122(),
                    'event' => $event ? [
                        'id' => $event->getId()->toRfc4122(),
                        'name' => $event->getName(),
                        'eventDate' => $event->getEventDate()?->format(DATE_ATOM),
                        'venue' => $event->getVenue(),
                    ] : [
                        'id' => 'deleted',
                        'name' => 'Event Deleted',
                        'eventDate' => null,
                        'venue' => null,
                    ],
                    'ticketType' => $ticketType ? [
                        'id' => $ticketType->getId()->toRfc4122(),
                        'name' => $ticketType->getName(),
                    ] : [
                        'id' => 'unknown',
                        'name' => 'Unknown Ticket Type',
                    ],
                    'price' => $t->getPrice(),
                    'priceFormatted' => Money::fromInt($t->getPrice())->format(),
                    'status' => $t->getStatus(),
                    'purchasedAt' => $t->getPurchasedAt()?->format(DATE_ATOM),
                    'createdAt' => $t->getCreatedAt()->format(DATE_ATOM),
                    'qrCode' => $t->getQrCode(),
                ];
            }
            if (is_array($t)) {
                $price = (int) ($t['price'] ?? 0);
                $purchasedAt = $t['purchasedAt'] ?? null;
                return [
                    ...$t,
                    'priceFormatted' => Money::fromInt($price)->format(),
                    'purchasedAt' => $purchasedAt ? (new \DateTimeImmutable($purchasedAt))->format(DATE_ATOM) : null,
                ];
            }
            return (array) $t;
        };

        return ['tickets' => array_map($map, $tickets)];
    }

    public function presentCancel(?string $message = 'Ticket cancelled'): array
    {
        return ['message' => $message];
    }

    public function presentUserTicketsDto(array $tickets): UserTicketsDTO
    {
        $mapped = $this->presentUserTickets($tickets);
        return new UserTicketsDTO(tickets: $mapped['tickets']);
    }
}
