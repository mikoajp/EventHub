<?php

namespace App\Tests\Integration\Messenger;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MessengerConfigTest extends KernelTestCase
{
    public function testBusAvailable(): void
    {
        self::bootKernel();
        $bus = static::getContainer()->get(MessageBusInterface::class);
        $this->assertInstanceOf(MessageBusInterface::class, $bus);
    }
}
