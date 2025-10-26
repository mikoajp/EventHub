<?php

namespace App\Tests\Unit\Service;

use App\Entity\IdempotencyKey;
use App\Repository\IdempotencyKeyRepository;
use App\Service\IdempotencyService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class IdempotencyServiceTtlRetryTest extends TestCase
{
    private EntityManagerInterface $em;
    private IdempotencyKeyRepository $repo;
    private LoggerInterface $logger;
    private IdempotencyService $svc;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repo = $this->createMock(IdempotencyKeyRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->svc = new IdempotencyService($this->em, $this->repo, $this->logger);
    }

    public function testCacheHitReturnsResult(): void
    {
        $key = new IdempotencyKey('k1', 'Cmd');
        $result = ['ok' => true];
        $key->markAsCompleted($result);
        $this->repo->method('findByKey')->with('k1')->willReturn($key);
        $out = $this->svc->checkIdempotency('k1', 'Cmd');
        $this->assertSame($result, $out);
    }

    public function testFailedAllowsRetry(): void
    {
        $key = new IdempotencyKey('k2', 'Cmd');
        $key->markAsFailed('err');
        $this->repo->method('findByKey')->with('k2')->willReturn($key);
        $out = $this->svc->checkIdempotency('k2', 'Cmd');
        $this->assertNull($out);
    }
}
