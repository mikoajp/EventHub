<?php

namespace App\Service;

final class PaymentResult
{
    public function __construct(
        private bool $successful,
        private ?string $paymentId = null
    ) {}

    public function isSuccessful(): bool { return $this->successful; }
    public function getPaymentId(): ?string { return $this->paymentId; }
}
