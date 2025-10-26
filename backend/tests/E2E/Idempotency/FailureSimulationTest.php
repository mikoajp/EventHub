<?php

namespace App\Tests\E2E\Idempotency;

use PHPUnit\Framework\TestCase;

final class FailureSimulationTest extends TestCase
{
    public function testTimeoutSimulationPlaceholder(): void
    {
        $this->markTestSkipped('Timeout simulation requires worker orchestration and is skipped by default.');
    }

    public function testInternalServerErrorSimulationPlaceholder(): void
    {
        $this->markTestSkipped('500 simulation requires fault injection; skipped by default.');
    }

    public function testPartialFailureSimulationPlaceholder(): void
    {
        $this->markTestSkipped('Partial-fail simulation (payment ok, DB fail) requires transactional setup; skipped.');
    }
}
