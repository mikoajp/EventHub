<?php

namespace App\DTO;

final readonly class PaymentResultDTO
{
    public function __construct(
        public bool $success,
        public ?string $paymentId = null,
        public ?string $message = null,
        public ?string $transactionId = null,
        public ?int $amount = null,
        public ?string $currency = null,
        public ?\DateTimeInterface $processedAt = null,
        public bool $requiresAction = false,
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

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getProcessedAt(): ?\DateTimeInterface
    {
        return $this->processedAt;
    }

    public function requiresAction(): bool
    {
        return $this->requiresAction;
    }
}
