<?php

namespace App\Service;

use App\Entity\IdempotencyKey;
use App\Exception\Idempotency\CommandAlreadyProcessingException;
use App\Repository\IdempotencyKeyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for handling command idempotency
 */
class IdempotencyService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly IdempotencyKeyRepository $repository,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Check if command was already processed
     * Returns the cached result if found and completed
     */
    public function checkIdempotency(string $idempotencyKey, string $commandClass): ?array
    {
        $key = $this->repository->findByKey($idempotencyKey);

        if (!$key) {
            return null;
        }

        // If still processing, throw exception to prevent concurrent execution
        if ($key->isProcessing()) {
            $this->logger->warning('Idempotency key is still processing', [
                'idempotency_key' => $idempotencyKey,
                'command_class' => $commandClass
            ]);
            throw new CommandAlreadyProcessingException($idempotencyKey, $commandClass);
        }

        // If completed, return cached result
        if ($key->isCompleted()) {
            $this->logger->info('Returning cached result for idempotent command', [
                'idempotency_key' => $idempotencyKey,
                'command_class' => $commandClass
            ]);
            return $key->getResult();
        }

        // If failed, allow retry
        if ($key->isFailed()) {
            $this->logger->info('Previous execution failed, allowing retry', [
                'idempotency_key' => $idempotencyKey,
                'command_class' => $commandClass
            ]);
            return null;
        }

        return null;
    }

    /**
     * Start tracking idempotent command execution
     */
    public function startExecution(string $idempotencyKey, string $commandClass): IdempotencyKey
    {
        $key = new IdempotencyKey($idempotencyKey, $commandClass);
        
        $this->entityManager->persist($key);
        $this->entityManager->flush();

        $this->logger->info('Started idempotent command execution', [
            'idempotency_key' => $idempotencyKey,
            'command_class' => $commandClass
        ]);

        return $key;
    }

    /**
     * Mark command execution as completed with result
     */
    public function markCompleted(IdempotencyKey $key, array $result): void
    {
        $key->markAsCompleted($result);
        $this->entityManager->flush();

        $this->logger->info('Marked idempotent command as completed', [
            'idempotency_key' => $key->getIdempotencyKey(),
            'command_class' => $key->getCommandClass()
        ]);
    }

    /**
     * Mark command execution as failed
     */
    public function markFailed(IdempotencyKey $key, string $errorMessage): void
    {
        $key->markAsFailed($errorMessage);
        $this->entityManager->flush();

        $this->logger->warning('Marked idempotent command as failed', [
            'idempotency_key' => $key->getIdempotencyKey(),
            'command_class' => $key->getCommandClass(),
            'error' => $errorMessage
        ]);
    }
}
