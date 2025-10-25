<?php

namespace App\Tests\Integration\Messenger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerConfigTest extends KernelTestCase
{
    public function testBusAvailable(): void
    {
        self::bootKernel();
        $bus = static::getContainer()->get(MessageBusInterface::class);
        $this->assertInstanceOf(MessageBusInterface::class, $bus);
    }
}
