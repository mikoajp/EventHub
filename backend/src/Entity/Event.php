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

    #[ORM\Column(length: 50)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $status = self::STATUS_DRAFT;

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

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['event:read'])]
    private ?string $previousStatus = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'organizedEvents')]
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
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

    public function getPreviousStatus(): ?string
    {
        return $this->previousStatus;
    }

    public function setPreviousStatus(?string $previousStatus): static
    {
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
            if ($ticketType->getEvent() === $this) {
                $ticketType->setEvent(null);
            }
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
                $ticket->setEvent(null);
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


    #[Groups(['event:read'])]
    public function getTicketsSold(): int
    {
        if ($this->orders->count() > 0) {
            return $this->orders->reduce(function (int $total, Order $order) {
                return $total + $order->getOrderItems()->reduce(function (int $itemTotal, $orderItem) {
                        return $itemTotal + $orderItem->getQuantity();
                    }, 0);
            }, 0);
        }

        return $this->tickets->filter(fn(Ticket $ticket) =>
            $ticket->getStatus() === 'purchased'
        )->count();
    }

    #[Groups(['event:read'])]
    public function getAvailableTickets(): int
    {
        return $this->maxTickets - $this->getTicketsSold();
    }

    #[Groups(['event:read'])]
    public function getTotalRevenue(): float
    {
        if ($this->orders->count() > 0) {
            return $this->orders->reduce(function (float $total, Order $order) {
                return $total + $order->getTotalAmount();
            }, 0.0);
        }

        return $this->tickets
            ->filter(fn(Ticket $ticket) => $ticket->getStatus() === 'purchased')
            ->reduce(function (float $total, Ticket $ticket) {
                return $total + $ticket->getPrice();
            }, 0.0);
    }

    #[Groups(['event:read'])]
    public function getAttendeesCount(): int
    {
        return $this->attendees->count();
    }

    #[Groups(['event:read'])]
    public function getOrdersCount(): int
    {
        return $this->orders->count();
    }

    // Status check methods

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }


    public function canBeModified(): bool
    {
        return !$this->isCancelled() && !$this->isCompleted() &&
            (!$this->isPublished() || $this->getTicketsSold() === 0);
    }

    public function canBeCancelled(): bool
    {
        return !$this->isCancelled() && !$this->isCompleted();
    }

    public function canBePublished(): bool
    {
        return $this->isDraft() && $this->eventDate > new \DateTime();
    }

    public function canBeUnpublished(): bool
    {
        return $this->isPublished() && $this->getTicketsSold() === 0;
    }

    public function canBeCompleted(): bool
    {
        return $this->isPublished() && $this->eventDate < new \DateTime();
    }

    public function hasTicketsSold(): bool
    {
        return $this->getTicketsSold() > 0;
    }

    public function isSoldOut(): bool
    {
        return $this->getAvailableTickets() <= 0;
    }

    public function isUpcoming(): bool
    {
        return $this->eventDate > new \DateTime();
    }

    public function isPast(): bool
    {
        return $this->eventDate < new \DateTime();
    }

    #[Groups(['event:read'])]
    public function getDaysUntilEvent(): int
    {
        $now = new \DateTime();
        $diff = $now->diff($this->eventDate);
        return $this->eventDate > $now ? $diff->days : -$diff->days;
    }

    #[Groups(['event:read'])]
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_COMPLETED => 'Completed',
            default => 'Unknown'
        };
    }

    #[Groups(['event:read'])]
    public function getEventDateFormatted(): string
    {
        return $this->eventDate?->format('M j, Y \a\t g:i A') ?? '';
    }

    #[Groups(['event:read'])]
    public function getCreatedAtFormatted(): string
    {
        return $this->createdAt?->format('M j, Y \a\t g:i A') ?? '';
    }

    // Ticket type management

    public function getTicketTypeByName(string $name): ?TicketType
    {
        foreach ($this->ticketTypes as $ticketType) {
            if ($ticketType->getName() === $name) {
                return $ticketType;
            }
        }
        return null;
    }

    public function hasAvailableTicketType(): bool
    {
        foreach ($this->ticketTypes as $ticketType) {
            if ($ticketType->getAvailableQuantity() > 0) {
                return true;
            }
        }
        return false;
    }


    public function markAsCompleted(): static
    {
        if ($this->canBeCompleted()) {
            $this->setStatus(self::STATUS_COMPLETED);
        }
        return $this;
    }

    public function calculateOccupancyRate(): float
    {
        if ($this->maxTickets === 0) {
            return 0.0;
        }
        return ($this->getTicketsSold() / $this->maxTickets) * 100;
    }

    #[Groups(['event:read'])]
    public function getOccupancyRate(): float
    {
        return round($this->calculateOccupancyRate(), 2);
    }

    public function addToWaitingList(User $user): bool
    {
        // Implementation for waiting list functionality
        if ($this->isSoldOut() && !$this->attendees->contains($user)) {
            // Add to waiting list (would need a separate WaitingList entity)
            return true;
        }
        return false;
    }
}