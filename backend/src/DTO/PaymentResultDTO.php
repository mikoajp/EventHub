<?php

namespace App\DTO;

final readonly class PaymentResultDTO
{
    public function __construct(
        public bool $success,
        public ?string $paymentId = null,
        public ?string $message = null,
    ) {}

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }
}
