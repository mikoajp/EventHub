<?php

namespace App\Tests\Unit\Entity;

use App\Entity\TicketType;
use PHPUnit\Framework\TestCase;

final class TicketTypeTest extends TestCase
{
    public function testCreateTicketType(): void
    {
        $ticketType = new TicketType();
        
        $this->assertInstanceOf(TicketType::class, $ticketType);
        $this->assertNotNull($ticketType->getId());
    }

    public function testSetAndGetName(): void
    {
        $ticketType = new TicketType();
        $ticketType->setName('VIP Pass');
        
        $this->assertSame('VIP Pass', $ticketType->getName());
    }

    public function testSetAndGetPrice(): void
    {
        $ticketType = new TicketType();
        $ticketType->setPrice(5000);
        
        $this->assertSame(5000, $ticketType->getPrice());
    }

    public function testSetAndGetQuantity(): void
    {
        $ticketType = new TicketType();
        $ticketType->setQuantity(100);
        
        $this->assertSame(100, $ticketType->getQuantity());
    }

    public function testSetQuantityAlsoSetsRemainingQuantity(): void
    {
        $ticketType = new TicketType();
        $ticketType->setQuantity(100);
        
        $this->assertSame(100, $ticketType->getRemainingQuantity());
    }

    public function testSetAndGetRemainingQuantity(): void
    {
        $ticketType = new TicketType();
        $ticketType->setRemainingQuantity(50);
        
        $this->assertSame(50, $ticketType->getRemainingQuantity());
    }

    public function testGetAvailableQuantity(): void
    {
        $ticketType = new TicketType();
        $ticketType->setRemainingQuantity(25);
        
        $this->assertSame(25, $ticketType->getAvailableQuantity());
    }

    public function testGetPriceFormatted(): void
    {
        $ticketType = new TicketType();
        $ticketType->setPrice(5000);
        
        $formatted = $ticketType->getPriceFormatted();
        
        $this->assertIsString($formatted);
        $this->assertStringContainsString('50', $formatted);
    }

    public function testGetPriceInDollars(): void
    {
        $ticketType = new TicketType();
        $ticketType->setPrice(5000);
        
        $this->assertSame(50.0, $ticketType->getPriceInDollars());
    }

    public function testTicketsCollectionInitialized(): void
    {
        $ticketType = new TicketType();
        
        $this->assertNotNull($ticketType->getTickets());
        $this->assertCount(0, $ticketType->getTickets());
    }
}
