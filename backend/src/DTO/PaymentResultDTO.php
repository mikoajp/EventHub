<?php

namespace App\DTO;

final readonly class PaymentResultDTO
{
    public function __construct(
        public bool $success,
        public ?string $paymentId = null,
        public ?string $message = null,
    ) {}
}
