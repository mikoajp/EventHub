<?php

namespace App\Tests\Unit\Service;

use App\Entity\IdempotencyKey;
use App\Repository\IdempotencyKeyRepository;
use App\Service\IdempotencyService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class IdempotencyServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private IdempotencyKeyRepository $repository;
    private LoggerInterface $logger;
    private IdempotencyService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(IdempotencyKeyRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->service = new IdempotencyService(
            $this->entityManager,
            $this->repository,
            $this->logger
        );
    }

    public function testCheckIdempotencyReturnsNullWhenKeyNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('findByKey')
            ->with('test-key')
            ->willReturn(null);

        $result = $this->service->checkIdempotency('test-key', 'TestCommand');

        $this->assertNull($result);
    }

    public function testCheckIdempotencyReturnsResultWhenCompleted(): void
    {
        $expectedResult = ['ticket_ids' => ['123', '456']];
        
        $key = new IdempotencyKey('test-key', 'TestCommand');
        $key->markAsCompleted($expectedResult);

        $this->repository->expects($this->once())
            ->method('findByKey')
            ->with('test-key')
            ->willReturn($key);

        $result = $this->service->checkIdempotency('test-key', 'TestCommand');

        $this->assertSame($expectedResult, $result);
    }

    public function testCheckIdempotencyThrowsExceptionWhenProcessing(): void
    {
        $key = new IdempotencyKey('test-key', 'TestCommand');
        // Key remains in processing status

        $this->repository->expects($this->once())
            ->method('findByKey')
            ->with('test-key')
            ->willReturn($key);

        $this->expectException(\App\Exception\Idempotency\CommandAlreadyProcessingException::class);

        $this->service->checkIdempotency('test-key', 'TestCommand');
    }

    public function testCheckIdempotencyReturnsNullWhenFailed(): void
    {
        $key = new IdempotencyKey('test-key', 'TestCommand');
        $key->markAsFailed('Some error');

        $this->repository->expects($this->once())
            ->method('findByKey')
            ->with('test-key')
            ->willReturn($key);

        $result = $this->service->checkIdempotency('test-key', 'TestCommand');

        $this->assertNull($result);
    }

    public function testStartExecutionCreatesNewKey(): void
    {
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(IdempotencyKey::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $key = $this->service->startExecution('test-key', 'TestCommand');

        $this->assertInstanceOf(IdempotencyKey::class, $key);
        $this->assertSame('test-key', $key->getIdempotencyKey());
        $this->assertSame('TestCommand', $key->getCommandClass());
        $this->assertTrue($key->isProcessing());
    }

    public function testMarkCompletedUpdatesKeyStatus(): void
    {
        $key = new IdempotencyKey('test-key', 'TestCommand');
        $result = ['ticket_ids' => ['123']];

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->service->markCompleted($key, $result);

        $this->assertTrue($key->isCompleted());
        $this->assertSame($result, $key->getResult());
        $this->assertInstanceOf(\DateTimeImmutable::class, $key->getCompletedAt());
    }

    public function testMarkFailedUpdatesKeyStatus(): void
    {
        $key = new IdempotencyKey('test-key', 'TestCommand');
        $errorMessage = 'Payment failed';

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->service->markFailed($key, $errorMessage);

        $this->assertTrue($key->isFailed());
        $this->assertSame(['error' => $errorMessage], $key->getResult());
        $this->assertInstanceOf(\DateTimeImmutable::class, $key->getCompletedAt());
    }
}
