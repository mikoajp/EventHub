<?php

namespace App\Tests\Unit\Message\Command;

use App\Message\Command\Ticket\PurchaseTicketCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class PurchaseTicketCommandTest extends TestCase
{
    public function testCreateCommand(): void
    {
        $eventId = Uuid::v4()->toString();
        $ticketTypeId = Uuid::v4()->toString();
        $quantity = 2;
        $userId = Uuid::v4()->toString();
        $paymentMethodId = 'pm_test_123';

        $command = new PurchaseTicketCommand(
            $eventId,
            $ticketTypeId,
            $quantity,
            $userId,
            $paymentMethodId
        );

        $this->assertInstanceOf(PurchaseTicketCommand::class, $command);
        $this->assertSame($eventId, $command->eventId);
        $this->assertSame($ticketTypeId, $command->ticketTypeId);
        $this->assertSame($quantity, $command->quantity);
        $this->assertSame($userId, $command->userId);
        $this->assertSame($paymentMethodId, $command->paymentMethodId);
    }

    public function testCommandWithDefaultQuantity(): void
    {
        $eventId = Uuid::v4()->toString();
        $ticketTypeId = Uuid::v4()->toString();
        $userId = Uuid::v4()->toString();
        $paymentMethodId = 'pm_test_123';

        $command = new PurchaseTicketCommand(
            $eventId,
            $ticketTypeId,
            1,
            $userId,
            $paymentMethodId
        );

        $this->assertSame(1, $command->quantity);
    }

    public function testCommandPropertiesArePublic(): void
    {
        $command = new PurchaseTicketCommand(
            'event-1',
            'type-1',
            5,
            'user-1',
            'pm_test'
        );

        // Should be able to access public properties
        $this->assertIsString($command->eventId);
        $this->assertIsString($command->ticketTypeId);
        $this->assertIsInt($command->quantity);
        $this->assertIsString($command->userId);
        $this->assertIsString($command->paymentMethodId);
    }
}
