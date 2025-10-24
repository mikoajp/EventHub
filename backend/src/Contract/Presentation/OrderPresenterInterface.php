<?php

namespace App\Contract\Presentation;

use App\Entity\Order;

interface OrderPresenterInterface
{
    public function presentSummary(Order $order): array;
    public function presentDetails(Order $order): array;
}
