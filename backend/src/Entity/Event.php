<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Enum\EventStatus;
use App\Repository\EventRepository;
use App\State\EventStateProcessor;
use App\State\EventStateProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'events')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('PUBLIC_ACCESS')",
            provider: EventStateProvider::class
        ),
        new Get(
            security: "is_granted('PUBLIC_ACCESS')",
            provider: EventStateProvider::class
        ),
        new Post(
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            securityMessage: "You must be logged in to create an event.",
            processor: EventStateProcessor::class
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN') or object.getOrganizer() == user",
            securityMessage: "You can only edit your own events.",
            processor: EventStateProcessor::class
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN') or object.getOrganizer() == user",
            securityMessage: "You can only delete your own events."
        )
    ],
    normalizationContext: ['groups' => ['event:read']],
    denormalizationContext: ['groups' => ['event:write']]
)]
class Event
{
    // Keep constants for backward compatibility
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups(['event:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Groups(['event:read', 'event:write'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull]
    #[Assert\GreaterThan('now')]
    #[Groups(['event:read', 'event:write'])]
    private ?\DateTimeInterface $eventDate = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['event:read', 'event:write'])]
    private ?string $venue = null;

    #[ORM\Column]
    #[Assert\Positive]
    #[Groups(['event:read', 'event:write'])]
    private ?int $maxTickets = null;

    #[ORM\Column(type: 'string', length: 50, enumType: EventStatus::class)]
    #[Groups(['event:read', 'event:write'])]
    private EventStatus $status = EventStatus::DRAFT;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['event:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['event:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['event:read'])]
    private ?\DateTimeInterface $publishedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['event:read'])]
    private ?\DateTimeInterface $cancelledAt = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true, enumType: EventStatus::class)]
    #[Groups(['event:read'])]
    private ?EventStatus $previousStatus = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'organizedEvents', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['event:read'])]
    private ?User $organizer = null;

    #[ORM\OneToMany(targetEntity: TicketType::class, mappedBy: 'event', cascade: ['persist', 'remove'])]
    #[Groups(['event:read', 'event:write'])]
    private Collection $ticketTypes;

    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'event')]
    #[Groups(['event:read'])]
    private Collection $tickets;

    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'event')]
    #[Groups(['event:read'])]
    private Collection $orders;

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'event_attendees')]
    #[Groups(['event:read'])]
    private Collection $attendees;

    public function __construct()
    {
        $this->ticketTypes = new ArrayCollection();
        $this->tickets = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->attendees = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getEventDate(): ?\DateTimeInterface
    {
        return $this->eventDate;
    }

    public function setEventDate(\DateTimeInterface|string $eventDate): static
    {
        if (is_string($eventDate)) {
            $eventDate = new \DateTime($eventDate);
        }
        $this->eventDate = $eventDate;
        return $this;
    }

    public function getVenue(): ?string
    {
        return $this->venue;
    }

    public function setVenue(string $venue): static
    {
        $this->venue = $venue;
        return $this;
    }

    public function getMaxTickets(): ?int
    {
        return $this->maxTickets;
    }

    public function setMaxTickets(int $maxTickets): static
    {
        $this->maxTickets = $maxTickets;
        return $this;
    }

    public function getStatus(): EventStatus
    {
        return $this->status;
    }

    public function setStatus(EventStatus|string $status): static
    {
        if (is_string($status)) {
            $status = EventStatus::from($status);
        }
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getPublishedAt(): ?\DateTimeInterface
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeInterface $publishedAt): static
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function getCancelledAt(): ?\DateTimeInterface
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeInterface $cancelledAt): static
    {
        $this->cancelledAt = $cancelledAt;
        return $this;
    }

    public function getPreviousStatus(): ?EventStatus
    {
        return $this->previousStatus;
    }

    public function setPreviousStatus(EventStatus|string|null $previousStatus): static
    {
        if (is_string($previousStatus)) {
            $previousStatus = EventStatus::from($previousStatus);
        }
        $this->previousStatus = $previousStatus;
        return $this;
    }

    public function getOrganizer(): ?User
    {
        return $this->organizer;
    }

    public function setOrganizer(?User $organizer): static
    {
        $this->organizer = $organizer;
        return $this;
    }

    /**
     * @return Collection<int, TicketType>
     */
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
            // Note: We don't set event to null because TicketType::event is non-nullable
            // Doctrine will handle orphan removal if configured with orphanRemoval=true
            // For now, just remove from collection
        }

        return $this;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): static
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets->add($ticket);
            $ticket->setEvent($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): static
    {
        if ($this->tickets->removeElement($ticket)) {
            if ($ticket->getEvent() === $this) {
                // keep relation; Ticket::event is non-nullable
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setEvent($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            if ($order->getEvent() === $this) {
                $order->setEvent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getAttendees(): Collection
    {
        return $this->attendees;
    }

    public function addAttendee(User $attendee): static
    {
        if (!$this->attendees->contains($attendee)) {
            $this->attendees->add($attendee);
        }

        return $this;
    }

    public function removeAttendee(User $attendee): static
    {
        $this->attendees->removeElement($attendee);
        return $this;
    }
    public function isPublished(): bool
    {
        return $this->status === EventStatus::PUBLISHED;
    }

    public function isDraft(): bool
    {
        return $this->status === EventStatus::DRAFT;
    }

    public function isCancelled(): bool
    {
        return $this->status === EventStatus::CANCELLED;
    }

    public function isCompleted(): bool
    {
        return $this->status === EventStatus::COMPLETED;
    }
}