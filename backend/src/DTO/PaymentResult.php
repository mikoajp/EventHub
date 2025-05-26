<?php

namespace App\DTO;

final readonly class PaymentResult
{
    public function __construct(
        public bool $success,
        public ?string $paymentId,
        public string $message
    ) {}

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}