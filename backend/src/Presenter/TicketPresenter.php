<?php

namespace App\Presenter;

use App\Contract\Presentation\TicketPresenterInterface;
use App\Domain\ValueObject\Money;
use App\Entity\Ticket;

final class TicketPresenter implements \App\Contract\Presentation\TicketPresenterInterface
{
    public function presentAvailability(array $availability): array
    {
        return $availability;
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

    public function presentUserTickets(array $tickets): array
    {
        $map = function ($t): array {
            if ($t instanceof Ticket) {
                return [
                    'id' => $t->getId()?->toRfc4122(),
                    'event' => [
                        'id' => $t->getEvent()?->getId()?->toRfc4122(),
                        'name' => $t->getEvent()?->getName(),
                        'eventDate' => $t->getEvent()?->getEventDate()?->format(DATE_ATOM),
                        'venue' => $t->getEvent()?->getVenue(),
                    ],
                    'ticketType' => [
                        'id' => $t->getTicketType()?->getId()?->toRfc4122(),
                        'name' => $t->getTicketType()?->getName(),
                    ],
                    'price' => $t->getPrice(),
                    'priceFormatted' => Money::fromInt($t->getPrice())->format(),
                    'status' => $t->getStatus(),
                    'purchasedAt' => $t->getPurchasedAt()?->format(DATE_ATOM),
                    'createdAt' => $t->getCreatedAt()?->format(DATE_ATOM),
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
}
