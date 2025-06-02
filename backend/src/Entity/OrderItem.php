<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
#[ORM\Table(name: 'order_items')]
class OrderItem
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups(['order:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'orderItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    #[ORM\ManyToOne(targetEntity: TicketType::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['order:read', 'order:write'])]
    private ?TicketType $ticketType = null;

    #[ORM\Column]
    #[Groups(['order:read', 'order:write'])]
    private ?int $quantity = null;

    #[ORM\Column]
    #[Groups(['order:read', 'order:write'])]
    private ?int $unitPrice = null; // Price in cents at time of purchase

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;
        return $this;
    }

    public function getTicketType(): ?TicketType
    {
        return $this->ticketType;
    }

    public function setTicketType(?TicketType $ticketType): static
    {
        $this->ticketType = $ticketType;
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getUnitPrice(): ?int
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(int $unitPrice): static
    {
        $this->unitPrice = $unitPrice;
        return $this;
    }

    // Business logic methods

    #[Groups(['order:read'])]
    public function getTotalPrice(): int
    {
        return $this->quantity * $this->unitPrice;
    }

    #[Groups(['order:read'])]
    public function getTotalPriceFormatted(): string
    {
        return number_format($this->getTotalPrice() / 100, 2);
    }

    #[Groups(['order:read'])]
    public function getUnitPriceFormatted(): string
    {
        return number_format($this->unitPrice / 100, 2);
    }

    #[Groups(['order:read'])]
    public function getTicketTypeName(): ?string
    {
        return $this->ticketType?->getName();
    }

    #[Groups(['order:read'])]
    public function getEventName(): ?string
    {
        return $this->ticketType?->getEvent()?->getName();
    }
}