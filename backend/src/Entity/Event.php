<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\EventRepository;
use App\State\EventStateProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Patch(),
        new Delete()
    ],
    normalizationContext: ['groups' => ['event:read']],
    denormalizationContext: ['groups' => ['event:write']]
)]
class Event
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[Groups(['event:read'])]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    #[Groups(['event:read', 'event:write'])]
    private string $name;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Groups(['event:read', 'event:write'])]
    private string $description;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\GreaterThan('now')]
    #[Groups(['event:read', 'event:write'])]
    private \DateTimeImmutable $eventDate;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['event:read', 'event:write'])]
    private string $venue;

    #[ORM\Column]
    #[Assert\Positive]
    #[Groups(['event:read', 'event:write'])]
    private int $maxTickets;

    #[ORM\Column(length: 20)]
    #[Groups(['event:read', 'event:write'])]
    private string $status = self::STATUS_DRAFT;

    #[ORM\ManyToOne(inversedBy: 'organizedEvents')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['event:read', 'event:write'])]
    private User $organizer;

    #[ORM\Column]
    #[Groups(['event:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    #[Groups(['event:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(targetEntity: TicketType::class, mappedBy: 'event', cascade: ['persist', 'remove'])]
    #[Groups(['event:read'])]
    private Collection $ticketTypes;

    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'event')]
    private Collection $tickets;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
        $this->ticketTypes = new ArrayCollection();
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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getEventDate(): \DateTimeImmutable
    {
        return $this->eventDate;
    }

    public function setEventDate(\DateTimeImmutable $eventDate): static
    {
        $this->eventDate = $eventDate;
        return $this;
    }

    public function getVenue(): string
    {
        return $this->venue;
    }

    public function setVenue(string $venue): static
    {
        $this->venue = $venue;
        return $this;
    }

    public function getMaxTickets(): int
    {
        return $this->maxTickets;
    }

    public function setMaxTickets(int $maxTickets): static
    {
        $this->maxTickets = $maxTickets;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getOrganizer(): User
    {
        return $this->organizer;
    }

    public function setOrganizer(User $organizer): static
    {
        $this->organizer = $organizer;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getTicketTypes(): Collection
    {
        return $this->ticketTypes;
    }

    public function addTicketType(TicketType $ticketType): static
    {
        if (!$this->ticketTypes->contains($ticketType)) {
            $this->ticketTypes->add($ticketType);
            $ticketType->setEvent($this);
        }
        return $this;
    }

    public function removeTicketType(TicketType $ticketType): static
    {
        if ($this->ticketTypes->removeElement($ticketType)) {
            if ($ticketType->getEvent() === $this) {
                $ticketType->setEvent(null);
            }
        }
        return $this;
    }

    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    #[Groups(['event:read'])]
    public function getTicketsSold(): int
    {
        return $this->tickets->filter(fn(Ticket $ticket) => 
            $ticket->getStatus() === Ticket::STATUS_PURCHASED
        )->count();
    }

    #[Groups(['event:read'])]
    public function getAvailableTickets(): int
    {
        return $this->maxTickets - $this->getTicketsSold();
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}