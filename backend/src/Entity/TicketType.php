<?php

namespace App\Entity;

use App\Repository\TicketTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TicketTypeRepository::class)]
#[ORM\Table(name: "ticket_type")]
class TicketType
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[Groups(['event:read'])]
    private Uuid $id;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['event:read', 'ticket_type:write'])]
    private string $name;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    #[Assert\PositiveOrZero]
    #[Groups(['event:read', 'ticket_type:write'])]
    private float $price;

    #[ORM\Column(type: "integer")]
    #[Assert\Positive]
    #[Groups(['event:read', 'ticket_type:write'])]
    private int $quantity;

    #[ORM\Column(type: "integer")]
    #[Assert\PositiveOrZero]
    private int $remainingQuantity;

    #[ORM\Column(type: "datetime_immutable")]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'ticketTypes')]
    #[ORM\JoinColumn(nullable: false)]
    private Event $event;

    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'ticketType')]
    private Collection $tickets;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->tickets = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->remainingQuantity = 0;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getRemainingQuantity(): int
    {
        return $this->remainingQuantity;
    }

    public function setRemainingQuantity(int $remainingQuantity): static
    {
        $this->remainingQuantity = $remainingQuantity;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;
        return $this;
    }

    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    #[Groups(['event:read'])]
    public function getAvailableQuantity(): int
    {
        return $this->remainingQuantity;
    }

    #[Groups(['event:read'])]
    public function getPriceFormatted(): string
    {
        return number_format($this->price, 2);
    }
}