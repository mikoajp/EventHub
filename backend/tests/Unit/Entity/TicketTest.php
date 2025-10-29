<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Ticket;
use PHPUnit\Framework\TestCase;

final class TicketTest extends TestCase
{
    public function testTicketStatusConstants(): void
    {
        $this->assertSame('reserved', Ticket::STATUS_RESERVED);
        $this->assertSame('purchased', Ticket::STATUS_PURCHASED);
        $this->assertSame('cancelled', Ticket::STATUS_CANCELLED);
        $this->assertSame('refunded', Ticket::STATUS_REFUNDED);
    }

    public function testCreateTicket(): void
    {
        $ticket = new Ticket();
        
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertNotNull($ticket->getId());
    }

    public function testSetAndGetStatus(): void
    {
        $ticket = new Ticket();
        $ticket->setStatus(Ticket::STATUS_PURCHASED);
        
        $this->assertSame(Ticket::STATUS_PURCHASED, $ticket->getStatus()->value);
    }

    public function testSetAndGetPrice(): void
    {
        $ticket = new Ticket();
        $ticket->setPrice(5000);
        
        $this->assertSame(5000, $ticket->getPrice());
    }

    public function testPurchasedAtIsNullByDefault(): void
    {
        $ticket = new Ticket();
        
        $this->assertNull($ticket->getPurchasedAt());
    }

    public function testSetAndGetPurchasedAt(): void
    {
        $ticket = new Ticket();
        $date = new \DateTimeImmutable();
        $ticket->setPurchasedAt($date);
        
        $this->assertSame($date, $ticket->getPurchasedAt());
    }
}
