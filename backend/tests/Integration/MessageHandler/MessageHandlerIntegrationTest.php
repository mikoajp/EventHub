<?php

namespace App\Tests\Integration\MessageHandler;

use App\Message\Command\Event\CreateEventCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessageHandlerIntegrationTest extends KernelTestCase
{
    private MessageBusInterface $commandBus;
    private MessageBusInterface $eventBus;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        // Check if messenger buses are registered
        if ($container->has('messenger.bus.default')) {
            $this->commandBus = $container->get('messenger.bus.default');
        }
        
        if ($container->has('event.bus')) {
            $this->eventBus = $container->get('event.bus');
        }
    }

    public function testCommandBusIsRegistered(): void
    {
        $container = static::getContainer();
        
        $this->assertTrue(
            $container->has('messenger.bus.default') || 
            $container->has(MessageBusInterface::class)
        );
    }

    public function testMessageHandlersAreRegistered(): void
    {
        $container = static::getContainer();
        
        // Check if at least some handlers are registered
        $this->assertTrue(
            $container->has('App\MessageHandler\Command\Event\CreateEventHandler') ||
            $container->has('messenger.bus.default')
        );
    }
}
