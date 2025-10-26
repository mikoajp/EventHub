<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\TicketRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            securityPostDenormalize: "is_granted('IS_AUTHENTICATED_FULLY')",
            securityMessage: "You must be logged in to view tickets."
        ),
        new Get(
            security: "is_granted('TICKET_VIEW', object)",
            securityMessage: "You can only view your own tickets, or tickets for events you organize."
        ),
        new Post(
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            securityMessage: "You must be logged in to purchase tickets."
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "Only administrators can modify tickets."
        ),
        new Delete(
            security: "is_granted('TICKET_CANCEL', object)",
            securityMessage: "You can only cancel your own tickets."
        )
    ],
    normalizationContext: ['groups' => ['ticket:read']],
    denormalizationContext: ['groups' => ['ticket:write']]
)]
class Ticket
{
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_PURCHASED = 'purchased';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_USED = 'used';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[Groups(['ticket:read'])]
    private Uuid $id;

    #[ORM\ManyToOne(inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ticket:read'])]
    private Event $event;

    #[ORM\ManyToOne(inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ticket:read'])]
    private User $user;

    #[ORM\ManyToOne(inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ticket:read'])]
    private TicketType $ticketType;

    #[ORM\Column]
    #[Groups(['ticket:read'])]
    private int $price; // in cents

    #[ORM\Column(length: 20)]
    #[Groups(['ticket:read'])]
    private string $status = self::STATUS_RESERVED;

    #[ORM\Column(nullable: true)]
    #[Groups(['ticket:read'])]
    private ?\DateTimeImmutable $purchasedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['ticket:read'])]
    private ?string $qrCode = null;

    #[ORM\Column]
    #[Groups(['ticket:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): static
    {
        $this->event = $event;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getTicketType(): TicketType
    {
        return $this->ticketType;
    }

    public function setTicketType(TicketType $ticketType): static
    {
        $this->ticketType = $ticketType;
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getPurchasedAt(): ?\DateTimeImmutable
    {
        return $this->purchasedAt;
    }

    public function setPurchasedAt(?\DateTimeImmutable $purchasedAt): static
    {
        $this->purchasedAt = $purchasedAt;
        return $this;
    }

    public function getQrCode(): ?string
    {
        return $this->qrCode;
    }

    public function setQrCode(?string $qrCode): static
    {
        $this->qrCode = $qrCode;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function markAsPurchased(): void
    {
        $this->status = self::STATUS_PURCHASED;
        $this->purchasedAt = new \DateTimeImmutable();
        $this->qrCode = $this->generateQrCode();
    }

    private function generateQrCode(): string
    {
        return 'QR_' . strtoupper(substr($this->id->toBase58(), 0, 10));
    }

    #[Groups(['ticket:read'])]
    public function getPriceFormatted(): string
    {
        return number_format($this->price / 100, 2);
    }
}