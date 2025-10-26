<?php

namespace App\Exception\Order;

class OrderNotFoundException extends OrderException
{
    public function __construct(string $orderId)
    {
        parent::__construct(
            sprintf('Order with ID "%s" not found', $orderId),
            404
        );
    }
}
