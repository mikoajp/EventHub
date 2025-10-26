<?php

namespace App\Message\Command\Ticket;

final readonly class PurchaseTicketCommand
{
    public function __construct(
        public string $eventId,
        public string $ticketTypeId,
        public int $quantity,
        public string $userId,
        public string $paymentMethodId,
        public ?string $idempotencyKey = null
    ) {}

    /**
     * Generate idempotency key from command data if not provided
     */
    public function getIdempotencyKey(): string
    {
        if ($this->idempotencyKey) {
            return $this->idempotencyKey;
        }

        // Prefer client-provided key. If missing, generate deterministic fallback with stable prefix.
        // NOTE: server-generated keys risk collisions across different clients; treat as best-effort.
        return 'srv_' . hash('sha256', implode('|', [
            $this->eventId,
            $this->ticketTypeId,
            $this->quantity,
            $this->userId,
            $this->paymentMethodId
        ]));
    }
}