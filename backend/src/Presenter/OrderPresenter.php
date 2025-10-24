<?php

namespace App\Presenter;

use App\Contract\Presentation\OrderPresenterInterface;
use App\Domain\ValueObject\Money;
use App\Entity\Order;

final class OrderPresenter implements OrderPresenterInterface
{
    public function presentSummary(Order $order): array
    {
        return [
            'id' => $order->getId()?->toRfc4122(),
            'status' => $order->getStatus(),
            'totalAmount' => $order->getTotalAmount(),
            'totalAmountFormatted' => Money::fromInt((int) $order->getTotalAmount())->format(),
            'createdAt' => $order->getCreatedAt()?->format(DATE_ATOM),
        ];
    }

    public function presentDetails(Order $order): array
    {
        $items = [];
        foreach ($order->getOrderItems() as $item) {
            $items[] = [
                'ticketType' => [
                    'id' => $item->getTicketType()?->getId()?->toRfc4122(),
                    'name' => $item->getTicketType()?->getName(),
                ],
                'quantity' => $item->getQuantity(),
                'unitPrice' => $item->getUnitPrice(),
                'unitPriceFormatted' => Money::fromInt((int) $item->getUnitPrice())->format(),
                'totalPrice' => $item->getTotalPrice(),
                'totalPriceFormatted' => Money::fromInt((int) $item->getTotalPrice())->format(),
            ];
        }

        return [
            'id' => $order->getId()?->toRfc4122(),
            'status' => $order->getStatus(),
            'totalAmount' => $order->getTotalAmount(),
            'totalAmountFormatted' => Money::fromInt((int) $order->getTotalAmount())->format(),
            'createdAt' => $order->getCreatedAt()?->format(DATE_ATOM),
            'updatedAt' => $order->getUpdatedAt()?->format(DATE_ATOM),
            'event' => [
                'id' => $order->getEvent()?->getId()?->toRfc4122(),
                'name' => $order->getEvent()?->getName(),
            ],
            'user' => [
                'id' => $order->getUser()?->getId()?->toRfc4122(),
                'email' => $order->getUser()?->getEmail(),
            ],
            'items' => $items,
        ];
    }
}
