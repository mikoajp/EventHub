<?php

namespace App\DTO;

final readonly class PaymentResult
{
    public function __construct(
        public bool $success,
        public ?string $paymentId = null,
        public ?string $message = null,
    ) {}
}
