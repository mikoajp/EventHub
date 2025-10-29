<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Event;
use App\Entity\Ticket;
use App\Entity\TicketType;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Test ticket capacity constraints and status transitions
 */
final class TicketCapacityTest extends TestCase
{
    public function testTicketTypeHasQuantityLimit(): void
    {
        $ticketType = new TicketType();
        $ticketType->setQuantity(100);

        $this->assertSame(100, $ticketType->getQuantity());
    }

    public function testTicketTypeTracksRemainingQuantity(): void
    {
        $ticketType = new TicketType();
        $ticketType->setQuantity(100);

        // Initially, remaining should equal total
        $this->assertSame(100, $ticketType->getRemainingQuantity());

        // Simulate sold tickets
        $ticketType->setRemainingQuantity(75);
        $this->assertSame(75, $ticketType->getRemainingQuantity());
    }

    public function testTicketHasValidStatuses(): void
    {
        $this->assertSame('reserved', Ticket::STATUS_RESERVED);
        $this->assertSame('purchased', Ticket::STATUS_PURCHASED);
        $this->assertSame('cancelled', Ticket::STATUS_CANCELLED);
        $this->assertSame('refunded', Ticket::STATUS_REFUNDED);
    }

    public function testTicketStartsAsReserved(): void
    {
        $ticket = new Ticket();
        $ticket->setStatus(Ticket::STATUS_RESERVED);

        $this->assertSame(Ticket::STATUS_RESERVED, $ticket->getStatus()->value);
    }

    public function testTicketCanBeMarkedAsPurchased(): void
    {
        $ticket = new Ticket();
        $ticket->setStatus(Ticket::STATUS_RESERVED);

        $ticket->markAsPurchased();

        $this->assertSame(Ticket::STATUS_PURCHASED, $ticket->getStatus()->value);
        $this->assertInstanceOf(\DateTimeImmutable::class, $ticket->getPurchasedAt());
        $this->assertNotNull($ticket->getQrCode());
    }

    public function testTicketCanBeCancelled(): void
    {
        $ticket = new Ticket();
        $ticket->setStatus(Ticket::STATUS_RESERVED);

        $ticket->setStatus(Ticket::STATUS_CANCELLED);

        $this->assertSame(Ticket::STATUS_CANCELLED, $ticket->getStatus());
    }

    public function testTicketCanBeRefunded(): void
    {
        $ticket = new Ticket();
        $ticket->markAsPurchased();

        $ticket->setStatus(Ticket::STATUS_REFUNDED);

        $this->assertSame(Ticket::STATUS_REFUNDED, $ticket->getStatus());
    }

    public function testTicketPriceIsStoredInCents(): void
    {
        $ticket = new Ticket();
        $ticket->setPrice(5000); // $50.00

        $this->assertSame(5000, $ticket->getPrice());
        $this->assertSame('50.00', $ticket->getPriceFormatted());
    }

    public function testTicketGeneratesQrCodeOnPurchase(): void
    {
        $ticket = new Ticket();
        
        $this->assertNull($ticket->getQrCode());
        
        $ticket->markAsPurchased();
        
        $this->assertNotNull($ticket->getQrCode());
        $this->assertStringStartsWith('QR_', $ticket->getQrCode());
    }

    public function testMultipleTicketsGenerateUniqueQrCodes(): void
    {
        $ticket1 = new Ticket();
        $ticket2 = new Ticket();

        $ticket1->markAsPurchased();
        $ticket2->markAsPurchased();

        $this->assertNotSame($ticket1->getQrCode(), $ticket2->getQrCode());
    }
}
