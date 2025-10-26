<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Stores idempotency keys to prevent duplicate command execution
 */
#[ORM\Entity]
#[ORM\Table(name: 'idempotency_keys')]
#[ORM\UniqueConstraint(name: 'uniq_idem_key_context', columns: ['idempotency_key', 'command_class'])]
#[ORM\Index(columns: ['idempotency_key'], name: 'idx_idempotency_key')]
#[ORM\Index(columns: ['created_at'], name: 'idx_created_at')]
class IdempotencyKey
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(name: 'idempotency_key', type: Types::STRING, length: 255, unique: false)]
    private string $idempotencyKey;

    #[ORM\Column(name: 'command_class', type: Types::STRING, length: 255)]
    private string $commandClass;

    #[ORM\Column(type: Types::JSON)]
    private array $result;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $status; // 'processing', 'completed', 'failed'

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct(string $idempotencyKey, string $commandClass)
    {
        $this->id = Uuid::v4();
        $this->idempotencyKey = $idempotencyKey;
        $this->commandClass = $commandClass;
        $this->status = 'processing';
        $this->result = [];
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getIdempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function getCommandClass(): string
    {
        return $this->commandClass;
    }

    public function getResult(): array
    {
        return $this->result;
    }

    public function setResult(array $result): self
    {
        $this->result = $result;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function markAsCompleted(array $result): void
    {
        $this->status = 'completed';
        $this->result = $result;
        $this->completedAt = new \DateTimeImmutable();
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->status = 'failed';
        $this->result = ['error' => $errorMessage];
        $this->completedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }
}
