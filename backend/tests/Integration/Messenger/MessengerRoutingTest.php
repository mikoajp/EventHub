<?php

namespace App\Tests\Integration\Messenger;

use App\Tests\BaseTestCase;

final class MessengerRoutingTest extends BaseTestCase
{
    public function testTransportsAreRegistered(): void
    {
        $container = static::getContainer();

        $this->assertTrue($container->has('messenger.transport.async'));
        $this->assertTrue($container->has('messenger.transport.high_priority'));
        $this->assertTrue($container->has('messenger.transport.notifications'));
        $this->assertTrue($container->has('messenger.transport.failed'));
    }
}
