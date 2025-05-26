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

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['event:read', 'ticket_type:write'])]
    private int $price;

    #[ORM\Column]
    #[Assert\Positive]
    #[Groups(['event:read', 'ticket_type:write'])]
    private int $quantity;

    #[ORM\ManyToOne(inversedBy: 'ticketTypes')]
    #[ORM\JoinColumn(nullable: false)]
    private Event $event;

    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'ticketType')]
    private Collection $tickets;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->tickets = new ArrayCollection();
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

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
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
    public function getAvailable(): int
    {
        $sold = $this->tickets->filter(fn(Ticket $ticket) => 
            $ticket->getStatus() === Ticket::STATUS_PURCHASED
        )->count();
        
        return $this->quantity - $sold;
    }

    #[Groups(['event:read'])]
    public function getPriceFormatted(): string
    {
        return number_format($this->price / 100, 2);
    }

    public function getAvailableQuantity(): int
    {
        $sold = 0;
        foreach ($this->tickets as $ticket) {
            if ($ticket->getStatus() === 'purchased') {
            $sold++;
         }
    }
     return $this->quantity - $sold;
    }
}