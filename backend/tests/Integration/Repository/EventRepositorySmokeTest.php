<?php

namespace App\Tests\Integration\Repository;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class EventRepositorySmokeTest extends KernelTestCase
{
    public function testRepositoryServiceWires(): void
    {
        self::bootKernel();
        $repo = static::getContainer()->get(EventRepository::class);
        $this->assertInstanceOf(EventRepository::class, $repo);
    }
}
