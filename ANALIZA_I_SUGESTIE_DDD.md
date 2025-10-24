# 📊 Kompleksowa Analiza i Sugestie - Implementacja DDD w EventHub

**Data analizy:** 2025-01-29  
**Projekt:** EventHub - Platforma zarządzania wydarzeniami  
**Cel:** Osiągnięcie pełnej implementacji DDD zgodnie z PLAN_DDD_SZCZEGOLOWY.md

---

## 📋 Spis Treści

1. [Podsumowanie Wykonawcze](#podsumowanie-wykonawcze)
2. [Analiza Stanu Obecnego](#analiza-stanu-obecnego)
3. [Gap Analysis - Co brakuje](#gap-analysis-co-brakuje)
4. [Sugestie Implementacji](#sugestie-implementacji)
5. [Priorytety i Plan Działania](#priorytety-i-plan-działania)

---

## 🎯 Podsumowanie Wykonawcze

### Stan Projektu Względem Planu DDD

**Ogólny Progress:** ⚠️ **~45% ukończone**

#### Zrealizowane ✅
- Podstawowa struktura DDD (Application/Domain/Infrastructure)
- CQRS z Symfony Messenger (Commands, Queries, Events)
- Infrastruktura: Cache (Redis), Email (Symfony Mailer), Payment (Stripe stub)
- Część Application Services (5/5 utworzonych, ale nie wszystkie wykorzystane)
- Domain Services (6 serwisów domenowych)
- JWT Authentication z refresh tokens
- RabbitMQ messaging
- Mercure dla real-time

#### Częściowo Zrealizowane ⚠️
- Message Handlers (7/~15 potrzebnych)
- API Endpoints (brakuje kluczowych endpointów)
- DTO/Presenters (tylko częściowo, brak warstwy prezentacji)
- Migracja z legacy services (w trakcie, duża duplikacja)

#### Nie Zrealizowane ❌
- **API Platform State Providers/Processors** (plan wymaga, niemal brak)
- **Value Objects** (plan wymaga, brak implementacji)
- **Domain Events w pełni** (brak logiki w handlerach)
- **Agregaty DDD** (encje są fat models, brak agregatów)
- **Repository Patterns zgodnie z DDD** (używane Doctrine ORM directly)
- **Brak warstwy Presenter/ViewModel** (dane zwracane bezpośrednio)
- **Testy jednostkowe i integracyjne** (brak testów dla nowej architektury)

---

## 📊 Analiza Stanu Obecnego

### 1. Architektura Warstw

#### ✅ CO DZIAŁA DOBRZE

**Application Layer:**
```
backend/src/Application/Service/
├── EventApplicationService.php        ✅ Dobrze zaimplementowany
├── UserApplicationService.php         ✅ Wykorzystywany w AuthController
├── TicketApplicationService.php       ✅ Gotowy, ale niewykorzystany
├── PaymentApplicationService.php      ✅ Gotowy, ale niewykorzystany
└── NotificationApplicationService.php ⚠️ Konkuruje z legacy
```

**Domain Layer:**
```
backend/src/Domain/
├── Event/Service/
│   ├── EventDomainService.php         ✅ Dobrze używany
│   └── EventPublishingService.php     ✅ Dobrze używany
├── Ticket/Service/
│   ├── TicketDomainService.php        ✅ Gotowy
│   └── TicketAvailabilityService.php  ✅ Wykorzystywany
├── Payment/Service/
│   └── PaymentDomainService.php       ✅ Dobra logika domenowa
└── User/Service/
    └── UserDomainService.php          ✅ Wykorzystywany
```

**Infrastructure Layer:**
```
backend/src/Infrastructure/
├── Cache/RedisCacheAdapter.php        ✅ Świetna implementacja
├── Email/SymfonyMailerAdapter.php     ✅ Dobrze zaimplementowany
├── Payment/StripePaymentGateway.php   ✅ Zgodnie z interfejsem
├── Messaging/MercureAdapter.php       ✅ Real-time
└── Validation/                        ✅ Abstrakcja walidacji
```

#### ❌ CO NIE DZIAŁA / BRAKUJE

**1. Legacy Services - Masywna Duplikacja**

```
backend/src/Service/ (DO USUNIĘCIA)
├── EventService.php              ❌ 100% duplikacja z EventApplicationService
├── TicketService.php             ❌ 80% duplikacja z TicketApplicationService
├── PaymentService.php            ❌ 100% duplikacja z PaymentApplicationService + Infrastructure
├── AuthService.php               ❌ Zastąpiony przez UserApplicationService
├── NotificationService.php       ❌ Duplikacja NotificationApplicationService
├── EmailService.php              ❌ Duplikacja SymfonyMailerAdapter
├── EventStatisticsService.php    🔴 KONFLIKT - istnieje też w Domain/Analytics/
├── TicketAvailabilityService.php ⚠️ Duplikacja Domain Service
├── CacheService.php              ⚠️ Używany przez legacy, duplikuje RedisCacheAdapter
├── CacheStatsService.php         ⚠️ Niezależna funkcjonalność
├── ValidationService.php         ⚠️ Używany, ale duplikuje Infrastructure
└── ErrorHandlerService.php       ✅ Używany w kontrolerach (OK do zachowania)
```

**Problem:** Kontrolery obecnie używają TYLKO legacy lub nowe services, brak konsystencji.

**2. Brak Kluczowych Komponentów z Planu DDD**

### ❌ **Value Objects** (Plan wymaga, brak w projekcie)
Zgodnie z planem DDD, powinny istnieć Value Objects dla:
- `Money` (amount + currency)
- `Email` (z walidacją)
- `EventDate` (z logiką biznesową)
- `TicketStatus` (enum as VO)
- `PaymentStatus`

**Aktualnie:** Wszystkie dane przechowywane jako prymitywy w encjach.

### ❌ **Agregaty DDD** (Plan wymaga, niemal brak)
Plan zakłada agregaty:
- `Event` jako agregat root z `TicketType` jako child entities
- `Order` jako agregat root z `OrderItem`
- `User` jako agregat root

**Aktualnie:** Encje są "fat models" z logiką biznesową, ale nie działają jako agregaty.

### ❌ **API Platform State Providers/Processors** (Kluczowy brak!)
Plan DDD szczegółowo opisuje użycie:
- **State Providers** dla operacji GET (Users:get, Events:list)
- **State Processors** dla operacji POST/PUT/PATCH

**Aktualnie:** 
- Jest tylko 1 procesor: `EventStateProcessor` (ustawia organizera)
- Brak providers dla DTO/Presenters
- Dane zwracane bezpośrednio z encji (naruszenie DDD)

### ❌ **DTO/Presenters dla warstwy prezentacji**
Plan wymaga:
- `EventPresenter` dla transformacji Event → API response
- `UserPresenter` dla User → API response
- `TicketPresenter` dla Ticket → API response

**Aktualnie:**
- Jest tylko `EventDTO`, `UserRegistrationDTO`, `PaymentResult`
- Brak presenters - dane z encji trafiają bezpośrednio do API
- Naruszenie reguły: "Domain entities nie powinny wyciekać poza Application layer"

### ❌ **Brak Message Handlers dla kluczowych operacji**
**Istniejące handlery (7):**
- ✅ CreateEventHandler
- ✅ PublishEventHandler
- ✅ PurchaseTicketHandler
- ✅ ProcessPaymentHandler
- ✅ NotifyEventPublishedHandler
- ✅ SendTicketConfirmationHandler
- ✅ GetEventStatisticsHandler

**Brakujące handlery (zgodnie z Commands/Queries):**
- ❌ CancelEventHandler (command istnieje, brak handlera!)
- ❌ ReserveTicketHandler (command istnieje, brak handlera!)
- ❌ CheckTicketAvailabilityHandler (query istnieje, używany legacy service)
- ❌ RefundPaymentHandler (funkcja istnieje, brak CQRS)
- ❌ CancelTicketHandler (funkcja w serwisie, brak CQRS)

---

## 🔍 Gap Analysis - Co Brakuje Aby Osiągnąć Cel

### Faza 1: Infrastruktura i Podstawy (Plan - Fazy 1-2)

| Komponent | Plan DDD | Stan Obecny | Gap |
|-----------|----------|-------------|-----|
| Repository Interfaces | ✅ Wymagane | ❌ Brak | Używane Doctrine directly |
| Value Objects | ✅ Wymagane | ❌ Brak | Wszystko jako prymitywy |
| Domain Exceptions | ✅ Wymagane | ⚠️ Częściowo | Używane ogólne exceptions |
| Infrastructure Abstractions | ✅ Wymagane | ✅ OK | CacheInterface, EmailServiceInterface |
| Dependency Rule Enforcement | ✅ Wymagane | ❌ Brak | Brak PHPStan rules |

### Faza 2: Domain Layer (Plan - Faza 3)

| Komponent | Plan DDD | Stan Obecny | Gap |
|-----------|----------|-------------|-----|
| Agregaty | ✅ Wymagane | ❌ Brak | Fat models zamiast agregatów |
| Domain Services | ✅ Wymagane | ✅ Częściowo | 6/10 potrzebnych |
| Domain Events | ✅ Wymagane | ⚠️ Częściowo | Events istnieją, ale słaba logika |
| Business Rules w Domain | ✅ Wymagane | ⚠️ W encjach | Logika w encjach, nie w serwisach |

### Faza 3: Application Layer (Plan - Faza 4-5)

| Komponent | Plan DDD | Stan Obecny | Gap |
|-----------|----------|-------------|-----|
| Application Services | ✅ Wymagane | ✅ Częściowo | 5 serwisów, nie wszystkie używane |
| CQRS Commands | ✅ Wymagane | ✅ Częściowo | 6 commands, 2 bez handlerów |
| CQRS Queries | ✅ Wymagane | ⚠️ Słabo | 2 queries, większość via direct repo |
| Command Handlers | ✅ Wymagane | ⚠️ Częściowo | 4/6 commands ma handlery |
| Query Handlers | ✅ Wymagane | ❌ Słabo | Tylko 1 handler |
| Event Handlers | ✅ Wymagane | ⚠️ Częściowo | 3 handlery, prosta logika |

### Faza 4: Presentation Layer (Plan - Faza 6)

| Komponent | Plan DDD | Stan Obecny | Gap |
|-----------|----------|-------------|-----|
| API Platform Providers | ✅ Wymagane | ❌ Brak | Brak State Providers |
| API Platform Processors | ✅ Wymagane | ⚠️ 1 procesor | Tylko EventStateProcessor |
| DTO Input | ✅ Wymagane | ⚠️ Częściowo | 3 DTOs, brak validacji w niektórych |
| Presenters/ViewModels | ✅ Wymagane | ❌ Brak | Encje zwracane bezpośrednio |
| API Controllers | ✅ Wymagane | ⚠️ Mieszanka | Legacy + nowe services |

### Faza 5: Endpoints i Integracja (Plan - Faza 7-8)

| Endpoint | Plan DDD | Stan Obecny | Gap |
|----------|----------|-------------|-----|
| POST /api/auth/register | ✅ Wymagane | ✅ OK | Używa UserApplicationService |
| POST /api/auth/login | ✅ Wymagane | ✅ OK | Używa UserApplicationService |
| GET /api/auth/me | ✅ Wymagane | ✅ OK | Używa UserApplicationService |
| POST /api/events | ✅ Wymagane | ⚠️ Częściowo | EventController + legacy |
| GET /api/events | ✅ Wymagane | ⚠️ Częściowo | Działa, ale bez presenters |
| GET /api/events/{id} | ✅ Wymagane | ⚠️ Częściowo | Działa, ale bez presenters |
| POST /api/events/{id}/publish | ✅ Wymagane | ✅ OK | Używa CQRS |
| POST /api/events/{id}/cancel | ✅ Wymagane | ❌ Brak | Command istnieje, brak endpointu |
| GET /api/events/{id}/statistics | ✅ Wymagane | ❌ Brak | Handler istnieje, brak endpointu |
| POST /api/tickets/purchase | ✅ Wymagane | ❌ Brak | Handler istnieje, brak endpointu |
| GET /api/tickets/my | ✅ Wymagane | ❌ Brak | Funkcja w serwisie, brak endpointu |
| GET /api/tickets/availability | ✅ Wymagane | ✅ OK | Używa legacy TicketService |
| POST /api/tickets/{id}/cancel | ✅ Wymagane | ❌ Brak | Funkcja w serwisie, brak endpointu |

---

## 💡 Sugestie Implementacji

### PRIORYTET 1: Krytyczne braki (1-2 tygodnie)

#### 1.1 Dokończenie Brakujących Endpointów API

**Problem:** Gotowe handlery i serwisy, ale brak kontrolerów/endpointów.

**Brakujące endpointy (HIGH PRIORITY):**

```php
// backend/src/Controller/Api/TicketController.php - ROZSZERZYĆ

#[Route('/purchase', name: 'api_tickets_purchase', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function purchase(Request $request): JsonResponse
{
    // Użyć PurchaseTicketCommand + MessageBus
    // Handler już istnieje: PurchaseTicketHandler
}

#[Route('/my', name: 'api_tickets_my', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
public function getMyTickets(#[CurrentUser] User $user): JsonResponse
{
    // Użyć TicketApplicationService::getUserTickets()
    // Metoda już istnieje, tylko brak endpointu
}

#[Route('/{id}/cancel', name: 'api_tickets_cancel', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function cancel(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
{
    // Użyć TicketApplicationService::cancelTicket()
    // Metoda już istnieje
}
```

```php
// backend/src/Controller/Api/EventController.php - DODAĆ BRAKUJĄCE

#[Route('/{id}/cancel', name: 'api_events_cancel', methods: ['POST'])]
#[IsGranted('ROLE_ORGANIZER')]
public function cancel(string $id, Request $request): JsonResponse
{
    // Utworzyć CancelEventHandler dla CancelEventCommand
    // Command już istnieje, brak handlera
}

#[Route('/{id}/statistics', name: 'api_events_statistics', methods: ['GET'])]
public function getStatistics(string $id, Request $request): JsonResponse
{
    // Użyć GetEventStatisticsQuery + GetEventStatisticsHandler
    // Handler już istnieje, tylko brak endpointu
}
```

**Implementacja (3-5 dni pracy):**
1. Dodać brakujące metody do TicketController
2. Dodać brakujące metody do EventController
3. Utworzyć CancelEventHandler
4. Przetestować wszystkie nowe endpointy
5. Zaktualizować frontend API client (eventsApi, ticketsApi)

#### 1.2 Usunięcie Legacy Services - Migracja Kontrolerów

**Problem:** TicketController używa legacy `TicketService` zamiast `TicketApplicationService`.

**Aktualnie:**
```php
// backend/src/Controller/Api/TicketController.php
class TicketController extends AbstractController
{
    public function __construct(
        private readonly TicketService $ticketService,  // ❌ LEGACY
        private readonly ErrorHandlerService $errorHandler,
    ) {}
}
```

**Powinno być:**
```php
class TicketController extends AbstractController
{
    public function __construct(
        private readonly TicketApplicationService $ticketApplicationService, // ✅ NOWY
        private readonly MessageBusInterface $commandBus, // ✅ CQRS
        private readonly ErrorHandlerService $errorHandler,
    ) {}
}
```

**Plan migracji:**
1. ✅ AuthController - już używa `UserApplicationService`
2. ⚠️ EventController - używa `EventApplicationService` + legacy cache
3. ❌ TicketController - używa legacy `TicketService`
4. ❌ CacheController - używa legacy `CacheService`

**Kolejność usuwania (5-7 dni):**
```
1. Migracja TicketController → TicketApplicationService (1 dzień)
2. Usunięcie TicketService.php (po migracji)
3. Usunięcie PaymentService.php (używany tylko w handlerze - zamienić na PaymentApplicationService)
4. Usunięcie EventService.php (nieużywany, kompletnie zduplikowany)
5. Usunięcie AuthService.php (nieużywany, zastąpiony)
6. Usunięcie NotificationService.php (nieużywany lub zamienić na NotificationApplicationService)
7. Usunięcie EmailService.php (zamienić na SymfonyMailerAdapter)
8. Rozwiązanie konfliktu EventStatisticsService (2 wersje!)
```

#### 1.3 Implementacja Brakujących Message Handlers

**Brakujące handlery (MUST HAVE):**

```php
// backend/src/MessageHandler/Command/Event/CancelEventHandler.php - NOWY
#[AsMessageHandler]
final readonly class CancelEventHandler
{
    public function __construct(
        private EventRepository $eventRepository,
        private EventDomainService $eventDomainService,
        private MessageBusInterface $eventBus,
        private CacheInterface $cache
    ) {}

    public function __invoke(CancelEventCommand $command): void
    {
        // 1. Pobrać event
        // 2. Sprawdzić czy można anulować (EventDomainService)
        // 3. Anulować event
        // 4. Dispatch EventCancelledEvent
        // 5. Invalidate cache
    }
}
```

```php
// backend/src/MessageHandler/Command/Ticket/ReserveTicketHandler.php - NOWY
#[AsMessageHandler]
final readonly class ReserveTicketHandler
{
    public function __invoke(ReserveTicketCommand $command): string
    {
        // 1. Sprawdzić dostępność
        // 2. Zarezerwować bilet (status: RESERVED)
        // 3. Dispatch TicketReservedEvent
        // 4. Return ticket ID
    }
}
```

```php
// backend/src/MessageHandler/Query/Ticket/CheckTicketAvailabilityHandler.php - NOWY
#[AsMessageHandler]
final readonly class CheckTicketAvailabilityHandler
{
    public function __invoke(CheckTicketAvailabilityQuery $query): array
    {
        // Użyć TicketApplicationService::checkTicketAvailability()
        // Zamienić legacy TicketService na nowy
    }
}
```

**Czas realizacji:** 2-3 dni

---

### PRIORYTET 2: Architektura DDD - Fundamenty (2-3 tygodnie)

#### 2.1 Implementacja Value Objects

**Dlaczego to ważne:**
- Value Objects hermetyzują walidację i logikę biznesową
- Eliminują duplikację kodu walidacji
- Gwarantują niezmienność (immutability)
- Są kluczowym elementem DDD

**Do zaimplementowania:**

```php
// backend/src/Domain/Shared/ValueObject/Money.php
namespace App\Domain\Shared\ValueObject;

final readonly class Money
{
    public function __construct(
        private int $amount,      // W groszach/centach
        private Currency $currency
    ) {
        if ($amount < 0) {
            throw new \DomainException('Money amount cannot be negative');
        }
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function add(Money $other): self
    {
        if (!$this->currency->equals($other->currency)) {
            throw new \DomainException('Cannot add money with different currencies');
        }
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount 
            && $this->currency->equals($other->currency);
    }

    public function format(): string
    {
        $formatted = number_format($this->amount / 100, 2);
        return "{$formatted} {$this->currency->getCode()}";
    }
}
```

```php
// backend/src/Domain/Shared/ValueObject/Currency.php
namespace App\Domain\Shared\ValueObject;

enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case PLN = 'PLN';

    public function getCode(): string
    {
        return $this->value;
    }

    public function equals(Currency $other): bool
    {
        return $this === $other;
    }
}
```

```php
// backend/src/Domain/Shared/ValueObject/Email.php
namespace App\Domain\Shared\ValueObject;

final readonly class Email
{
    public function __construct(private string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \DomainException("Invalid email: {$value}");
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

```php
// backend/src/Domain/Event/ValueObject/EventDate.php
namespace App\Domain\Event\ValueObject;

final readonly class EventDate
{
    public function __construct(private \DateTimeImmutable $date)
    {
        $now = new \DateTimeImmutable();
        if ($date < $now) {
            throw new \DomainException('Event date cannot be in the past');
        }
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function isUpcoming(): bool
    {
        return $this->date > new \DateTimeImmutable();
    }

    public function isPast(): bool
    {
        return $this->date < new \DateTimeImmutable();
    }

    public function daysUntilEvent(): int
    {
        $now = new \DateTimeImmutable();
        $diff = $this->date->diff($now);
        return (int) $diff->format('%a');
    }
}
```

```php
// backend/src/Domain/Ticket/ValueObject/TicketStatus.php
namespace App\Domain\Ticket\ValueObject;

enum TicketStatus: string
{
    case RESERVED = 'reserved';
    case PURCHASED = 'purchased';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case USED = 'used';

    public function canBeUsed(): bool
    {
        return $this === self::PURCHASED;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::RESERVED, self::PURCHASED]);
    }

    public function canBeRefunded(): bool
    {
        return $this === self::PURCHASED;
    }
}
```

**Migracja encji do Value Objects (przykład):**

```php
// PRZED (Ticket.php)
private int $price;
private string $status = 'reserved';

// PO (Ticket.php)
private Money $price;
private TicketStatus $status = TicketStatus::RESERVED;
```

**Czas realizacji:** 5-7 dni

#### 2.2 Refaktoryzacja do Agregatów DDD

**Problem:** Encje są "fat models" z logiką biznesową rozrzuconą między encjami i serwisami.

**Agregat Event:**

```php
// backend/src/Domain/Event/Aggregate/EventAggregate.php
namespace App\Domain\Event\Aggregate;

use App\Domain\Event\Entity\Event;
use App\Domain\Event\Entity\TicketType;
use App\Domain\Event\ValueObject\EventDate;
use App\Domain\Shared\ValueObject\Money;

final class EventAggregate
{
    private Event $event;
    private array $ticketTypes = [];
    private array $domainEvents = [];

    private function __construct(Event $event)
    {
        $this->event = $event;
    }

    public static function create(
        string $name,
        string $description,
        EventDate $eventDate,
        string $venue,
        User $organizer
    ): self {
        $event = new Event();
        $event->setName($name)
              ->setDescription($description)
              ->setEventDate($eventDate->getDate())
              ->setVenue($venue)
              ->setOrganizer($organizer)
              ->setStatus(Event::STATUS_DRAFT);

        $aggregate = new self($event);
        
        // Domain Event
        $aggregate->recordDomainEvent(new EventCreated($event->getId()));
        
        return $aggregate;
    }

    public function addTicketType(
        string $name,
        Money $price,
        int $quantity
    ): void {
        if ($this->event->getStatus() !== Event::STATUS_DRAFT) {
            throw new \DomainException('Cannot add ticket types to published event');
        }

        $ticketType = new TicketType();
        $ticketType->setName($name)
                   ->setPrice($price->getAmount())
                   ->setQuantity($quantity)
                   ->setEvent($this->event);

        $this->ticketTypes[] = $ticketType;
        $this->event->addTicketType($ticketType);
    }

    public function publish(): void
    {
        if (!$this->canBePublished()) {
            throw new \DomainException('Event cannot be published');
        }

        $this->event->setStatus(Event::STATUS_PUBLISHED)
                    ->setPublishedAt(new \DateTimeImmutable());

        $this->recordDomainEvent(new EventPublished($this->event->getId()));
    }

    public function cancel(string $reason): void
    {
        if (!$this->canBeCancelled()) {
            throw new \DomainException('Event cannot be cancelled');
        }

        $this->event->setStatus(Event::STATUS_CANCELLED)
                    ->setCancellationReason($reason);

        $this->recordDomainEvent(new EventCancelled(
            $this->event->getId(),
            $reason
        ));
    }

    private function canBePublished(): bool
    {
        return $this->event->getStatus() === Event::STATUS_DRAFT
            && !empty($this->event->getName())
            && $this->event->getEventDate() !== null
            && !empty($this->ticketTypes);
    }

    private function canBeCancelled(): bool
    {
        return $this->event->getStatus() === Event::STATUS_PUBLISHED
            && $this->event->getEventDate() > new \DateTimeImmutable();
    }

    private function recordDomainEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function getDomainEvents(): array
    {
        return $this->domainEvents;
    }

    public function clearDomainEvents(): void
    {
        $this->domainEvents = [];
    }

    public function getEvent(): Event
    {
        return $this->event;
    }
}
```

**Użycie w Application Service:**

```php
// backend/src/Application/Service/EventApplicationService.php
public function createEvent(EventDTO $eventDTO, User $organizer): Event
{
    // Utworzenie agregatu
    $aggregate = EventAggregate::create(
        $eventDTO->name,
        $eventDTO->description,
        new EventDate($eventDTO->eventDate),
        $eventDTO->venue,
        $organizer
    );

    // Dodanie ticket types
    foreach ($eventDTO->ticketTypes as $ticketType) {
        $aggregate->addTicketType(
            $ticketType['name'],
            new Money($ticketType['price'], Currency::USD),
            $ticketType['quantity']
        );
    }

    // Persist
    $event = $aggregate->getEvent();
    $this->entityManager->persist($event);
    $this->entityManager->flush();

    // Dispatch domain events
    foreach ($aggregate->getDomainEvents() as $domainEvent) {
        $this->eventBus->dispatch($domainEvent);
    }

    return $event;
}
```

**Czas realizacji:** 7-10 dni

#### 2.3 Implementacja Repository Interfaces (DDD Pattern)

**Problem:** Repozytoria używane bezpośrednio z Doctrine, brak abstrakcji domenowej.

**Struktura:**

```php
// backend/src/Domain/Event/Repository/EventRepositoryInterface.php
namespace App\Domain\Event\Repository;

use App\Domain\Event\Aggregate\EventAggregate;
use App\Entity\Event;

interface EventRepositoryInterface
{
    public function save(EventAggregate $aggregate): void;
    public function findById(string $id): ?EventAggregate;
    public function findPublished(): array;
    public function findByOrganizer(string $organizerId): array;
    public function delete(EventAggregate $aggregate): void;
}
```

```php
// backend/src/Infrastructure/Persistence/Doctrine/EventRepository.php
namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Event\Repository\EventRepositoryInterface;
use App\Domain\Event\Aggregate\EventAggregate;
use Doctrine\ORM\EntityManagerInterface;

final class EventRepository implements EventRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function save(EventAggregate $aggregate): void
    {
        $event = $aggregate->getEvent();
        $this->entityManager->persist($event);
        $this->entityManager->flush();

        // Dispatch domain events
        foreach ($aggregate->getDomainEvents() as $domainEvent) {
            // Event dispatcher logic
        }
        $aggregate->clearDomainEvents();
    }

    public function findById(string $id): ?EventAggregate
    {
        $event = $this->entityManager
            ->getRepository(Event::class)
            ->find($id);

        return $event ? EventAggregate::fromExisting($event) : null;
    }

    // ... inne metody
}
```

**Konfiguracja services.yaml:**

```yaml
services:
    App\Domain\Event\Repository\EventRepositoryInterface:
        alias: App\Infrastructure\Persistence\Doctrine\EventRepository
```

**Czas realizacji:** 4-5 dni

---

### PRIORYTET 3: Presentation Layer - DTO/Presenters (1-2 tygodnie)

#### 3.1 Implementacja Presenters/ViewModels

**Problem:** Encje domenowe wyciekają bezpośrednio do API responses (naruszenie DDD).

**Struktura Presenters:**

```php
// backend/src/Presentation/Presenter/EventPresenter.php
namespace App\Presentation\Presenter;

use App\Entity\Event;

final class EventPresenter
{
    public function present(Event $event): array
    {
        return [
            'id' => $event->getId()->toString(),
            'name' => $event->getName(),
            'description' => $event->getDescription(),
            'eventDate' => $event->getEventDate()->format('c'),
            'venue' => $event->getVenue(),
            'status' => $event->getStatus(),
            'maxTickets' => $event->getMaxTickets(),
            'availableTickets' => $event->getAvailableTickets(),
            'organizer' => [
                'id' => $event->getOrganizer()->getId()->toString(),
                'name' => $event->getOrganizer()->getFullName(),
                'email' => $event->getOrganizer()->getEmail()
            ],
            'ticketTypes' => array_map(
                fn($tt) => $this->presentTicketType($tt),
                $event->getTicketTypes()->toArray()
            ),
            'createdAt' => $event->getCreatedAt()->format('c'),
            'updatedAt' => $event->getUpdatedAt()->format('c'),
            'publishedAt' => $event->getPublishedAt()?->format('c')
        ];
    }

    public function presentList(array $events): array
    {
        return array_map(fn($event) => $this->presentSummary($event), $events);
    }

    public function presentSummary(Event $event): array
    {
        return [
            'id' => $event->getId()->toString(),
            'name' => $event->getName(),
            'eventDate' => $event->getEventDate()->format('c'),
            'venue' => $event->getVenue(),
            'status' => $event->getStatus(),
            'availableTickets' => $event->getAvailableTickets(),
            'minPrice' => $this->getMinPrice($event),
            'organizerName' => $event->getOrganizer()->getFullName()
        ];
    }

    private function presentTicketType(TicketType $ticketType): array
    {
        return [
            'id' => $ticketType->getId()->toString(),
            'name' => $ticketType->getName(),
            'price' => $ticketType->getPrice(),
            'quantity' => $ticketType->getQuantity(),
            'soldCount' => $ticketType->getSoldCount(),
            'available' => $ticketType->getQuantity() - $ticketType->getSoldCount()
        ];
    }

    private function getMinPrice(Event $event): ?int
    {
        $prices = array_map(
            fn($tt) => $tt->getPrice(),
            $event->getTicketTypes()->toArray()
        );
        return !empty($prices) ? min($prices) : null;
    }
}
```

```php
// backend/src/Presentation/Presenter/TicketPresenter.php
namespace App\Presentation\Presenter;

use App\Entity\Ticket;

final class TicketPresenter
{
    public function present(Ticket $ticket): array
    {
        return [
            'id' => $ticket->getId()->toString(),
            'event' => [
                'id' => $ticket->getEvent()->getId()->toString(),
                'name' => $ticket->getEvent()->getName(),
                'eventDate' => $ticket->getEvent()->getEventDate()->format('c'),
                'venue' => $ticket->getEvent()->getVenue()
            ],
            'ticketType' => [
                'id' => $ticket->getTicketType()->getId()->toString(),
                'name' => $ticket->getTicketType()->getName()
            ],
            'price' => $ticket->getPrice(),
            'status' => $ticket->getStatus(),
            'purchaseDate' => $ticket->getPurchaseDate()?->format('c'),
            'qrCode' => $ticket->getQrCode(),
            'paymentId' => $ticket->getPaymentId()
        ];
    }

    public function presentList(array $tickets): array
    {
        return array_map(fn($ticket) => $this->present($ticket), $tickets);
    }
}
```

```php
// backend/src/Presentation/Presenter/UserPresenter.php
namespace App\Presentation\Presenter;

use App\Entity\User;

final class UserPresenter
{
    public function present(User $user): array
    {
        return [
            'id' => $user->getId()->toString(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'fullName' => $user->getFullName(),
            'roles' => $user->getRoles(),
            'createdAt' => $user->getCreatedAt()->format('c')
        ];
    }

    public function presentProfile(User $user): array
    {
        return array_merge($this->present($user), [
            'phone' => $user->getPhone(),
            'ticketCount' => $user->getTickets()->count(),
            'eventsOrganized' => $user->getOrganizedEvents()->count()
        ]);
    }
}
```

#### 3.2 API Platform State Providers

**Implementacja zgodnie z planem DDD:**

```php
// backend/src/State/Provider/EventCollectionProvider.php
namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Service\EventApplicationService;
use App\Presentation\Presenter\EventPresenter;

final class EventCollectionProvider implements ProviderInterface
{
    public function __construct(
        private EventApplicationService $eventApplicationService,
        private EventPresenter $eventPresenter
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $filters = $context['filters'] ?? [];
        $page = $filters['page'] ?? 1;
        $limit = $filters['itemsPerPage'] ?? 20;

        // Użycie Application Service
        $events = $this->eventApplicationService->getEventsWithFilters(
            $filters,
            ['eventDate' => 'ASC'],
            $page,
            $limit
        );

        // Transformacja przez Presenter
        return [
            'hydra:member' => $this->eventPresenter->presentList($events),
            'hydra:totalItems' => count($events)
        ];
    }
}
```

```php
// backend/src/State/Provider/EventItemProvider.php
namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Service\EventApplicationService;
use App\Presentation\Presenter\EventPresenter;

final class EventItemProvider implements ProviderInterface
{
    public function __construct(
        private EventApplicationService $eventApplicationService,
        private EventPresenter $eventPresenter
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $eventId = $uriVariables['id'] ?? null;
        
        if (!$eventId) {
            return null;
        }

        $event = $this->eventApplicationService->getEventById($eventId);
        
        if (!$event) {
            return null;
        }

        return $this->eventPresenter->present($event);
    }
}
```

```php
// backend/src/State/Provider/UserTicketsProvider.php
namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Service\TicketApplicationService;
use App\Presentation\Presenter\TicketPresenter;
use Symfony\Bundle\SecurityBundle\Security;

final class UserTicketsProvider implements ProviderInterface
{
    public function __construct(
        private TicketApplicationService $ticketApplicationService,
        private TicketPresenter $ticketPresenter,
        private Security $security
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            return null;
        }

        $ticketsData = $this->ticketApplicationService->getUserTickets($user);

        return [
            'hydra:member' => $ticketsData, // Już sformatowane przez service
            'hydra:totalItems' => count($ticketsData)
        ];
    }
}
```

#### 3.3 API Platform State Processors

```php
// backend/src/State/Processor/CreateEventProcessor.php
namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Message\Command\Event\CreateEventCommand;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class CreateEventProcessor implements ProcessorInterface
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            throw new \RuntimeException('User must be authenticated');
        }

        // Dispatch CQRS Command
        $command = new CreateEventCommand(
            $data['name'],
            $data['description'],
            new \DateTimeImmutable($data['eventDate']),
            $data['venue'],
            $data['maxTickets'],
            $user->getId()->toString(),
            $data['ticketTypes'] ?? []
        );

        $eventId = $this->commandBus->dispatch($command);

        return ['id' => $eventId, 'status' => 'created'];
    }
}
```

```php
// backend/src/State/Processor/PurchaseTicketProcessor.php
namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Message\Command\Ticket\PurchaseTicketCommand;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class PurchaseTicketProcessor implements ProcessorInterface
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            throw new \RuntimeException('User must be authenticated');
        }

        $command = new PurchaseTicketCommand(
            $data['eventId'],
            $data['ticketTypeId'],
            $user->getId()->toString(),
            $data['quantity'] ?? 1,
            $data['paymentMethodId']
        );

        $ticketIds = $this->commandBus->dispatch($command);

        return [
            'ticketIds' => $ticketIds,
            'status' => 'reserved',
            'message' => 'Tickets reserved, processing payment'
        ];
    }
}
```

**Konfiguracja w ApiResource:**

```php
// backend/src/Entity/Event.php
use App\State\Provider\EventCollectionProvider;
use App\State\Provider\EventItemProvider;
use App\State\Processor\CreateEventProcessor;

#[ApiResource(
    operations: [
        new Get(provider: EventItemProvider::class),
        new GetCollection(provider: EventCollectionProvider::class),
        new Post(processor: CreateEventProcessor::class, security: "is_granted('ROLE_ORGANIZER')"),
    ]
)]
class Event
{
    // ...
}
```

**Czas realizacji:** 7-10 dni

---

## 📅 Priorytety i Plan Działania

### Harmonogram Implementacji (8-12 tygodni)

#### **Sprint 1-2: Quick Wins i Krytyczne Braki (2 tygodnie)**

**Cel:** Dokończenie podstawowej funkcjonalności i usunięcie duplikacji

**Zadania:**
1. ✅ Dodać brakujące endpointy API (3-5 dni)
   - POST /api/tickets/purchase
   - GET /api/tickets/my
   - POST /api/tickets/{id}/cancel
   - POST /api/events/{id}/cancel
   - GET /api/events/{id}/statistics

2. ✅ Utworzyć brakujące handlery (2-3 dni)
   - CancelEventHandler
   - ReserveTicketHandler
   - CheckTicketAvailabilityHandler

3. ✅ Migracja kontrolerów z legacy na nowe services (3-4 dni)
   - TicketController → TicketApplicationService
   - ProcessPaymentHandler → PaymentApplicationService

4. ✅ Usunięcie legacy services (2-3 dni)
   - EventService.php
   - AuthService.php
   - PaymentService.php
   - TicketService.php
   - NotificationService.php
   - EmailService.php

**Deliverables:**
- ✅ Wszystkie kluczowe endpointy działają
- ✅ Brak duplikacji w Service layer
- ✅ Kontrolery używają wyłącznie Application Services lub CQRS
- ✅ Frontend może korzystać ze wszystkich funkcji

---

#### **Sprint 3-4: Value Objects i Domain Layer (2-3 tygodnie)**

**Cel:** Implementacja fundamentów DDD

**Zadania:**
1. ✅ Implementacja Value Objects (5-7 dni)
   - Money + Currency
   - Email
   - EventDate
   - TicketStatus
   - PaymentStatus
   - EventStatus

2. ✅ Migracja encji do użycia Value Objects (3-4 dni)
   - Event entity
   - Ticket entity
   - User entity
   - Order entity

3. ✅ Doctrine Custom Types dla Value Objects (2-3 dni)
   - MoneyType
   - EmailType
   - StatusTypes (enums)

4. ✅ Testy jednostkowe dla Value Objects (2-3 dni)

**Deliverables:**
- ✅ Wszystkie Value Objects zaimplementowane i przetestowane
- ✅ Encje używają Value Objects zamiast prymitywów
- ✅ Doctrine poprawnie serializuje/deserializuje VOs
- ✅ Pokrycie testami >80% dla VOs

---

#### **Sprint 5-6: Agregaty DDD i Repository Interfaces (2-3 tygodnie)**

**Cel:** Refaktoryzacja do pełnego DDD

**Zadania:**
1. ✅ Implementacja EventAggregate (4-5 dni)
   - Przeniesienie logiki biznesowej z Event entity
   - Domain events w agregacie
   - Invariants enforcement

2. ✅ Implementacja OrderAggregate (3-4 dni)
   - Order jako aggregate root
   - OrderItem jako child entity

3. ✅ Repository Interfaces (3-4 dni)
   - EventRepositoryInterface
   - TicketRepositoryInterface
   - UserRepositoryInterface
   - OrderRepositoryInterface

4. ✅ Doctrine Implementation (2-3 dni)
   - Infrastructure/Persistence/Doctrine/

5. ✅ Aktualizacja Application Services (3-4 dni)
   - Użycie agregatów zamiast encji

**Deliverables:**
- ✅ Agregaty zarządzają wszystką logiką biznesową
- ✅ Repository interfaces w Domain layer
- ✅ Implementacje w Infrastructure layer
- ✅ Application Services używają agregatów

---

#### **Sprint 7-8: Presentation Layer (1-2 tygodnie)**

**Cel:** Separacja warstwy prezentacji od domeny

**Zadania:**
1. ✅ Implementacja Presenters (4-5 dni)
   - EventPresenter
   - TicketPresenter
   - UserPresenter
   - OrderPresenter

2. ✅ API Platform State Providers (3-4 dni)
   - EventCollectionProvider
   - EventItemProvider
   - UserTicketsProvider
   - UserProfileProvider

3. ✅ API Platform State Processors (2-3 dni)
   - CreateEventProcessor
   - PurchaseTicketProcessor
   - UpdateUserProfileProcessor

4. ✅ Konfiguracja API Platform (1-2 dni)
   - ApiResource attributes
   - Routing configuration

**Deliverables:**
- ✅ Encje nie wyciekają do API responses
- ✅ Wszystkie endpointy używają Providers/Processors
- ✅ Prezentacja oddzielona od logiki biznesowej
- ✅ API zgodne z JSON:API/Hydra

---

#### **Sprint 9-10: Domain Events i Event Handlers (1-2 tygodnie)**

**Cel:** Wzbogacenie logiki asynchronicznej

**Zadania:**
1. ✅ Rozbudowa Event Handlers (4-5 dni)
   - SendEventCancellationEmailHandler
   - RefundTicketsHandler
   - UpdateStatisticsHandler
   - NotifyUsersHandler

2. ✅ Domain Event Dispatcher (2-3 dni)
   - Integration z Symfony Messenger
   - Event sourcing (opcjonalnie)

3. ✅ Saga Pattern dla złożonych operacji (3-4 dni)
   - Purchase Ticket Saga (reservation → payment → confirmation)

**Deliverables:**
- ✅ Pełna obsługa domain events
- ✅ Asynchroniczne przetwarzanie
- ✅ Compensation logic (saga)

---

#### **Sprint 11-12: Testy i Dokumentacja (1-2 tygodnie)**

**Cel:** Zapewnienie jakości i dokumentacji

**Zadania:**
1. ✅ Testy jednostkowe (5-6 dni)
   - Domain Services
   - Value Objects
   - Agregaty
   - Application Services

2. ✅ Testy integracyjne (3-4 dni)
   - API Endpoints
   - CQRS Handlers
   - Repository implementations

3. ✅ Dokumentacja (2-3 dni)
   - Architecture Decision Records (ADR)
   - API Documentation
   - Developer Guide

**Deliverables:**
- ✅ Pokrycie testami >70%
- ✅ Wszystkie krytyczne ścieżki przetestowane
- ✅ Dokumentacja aktualna i kompletna

---

## 🎯 Metryki Sukcesu (KPI)

### Kod
- ✅ 0% duplikacji w Service layer (aktualnie: ~60%)
- ✅ 100% kontrolerów używa Application Services lub CQRS
- ✅ 100% endpointów używa Presenters (aktualnie: 0%)
- ✅ >70% pokrycia testami (aktualnie: brak testów)

### Architektura
- ✅ Wszystkie Value Objects zaimplementowane (aktualnie: 0%)
- ✅ Wszystkie agregaty zaimplementowane (aktualnie: 0%)
- ✅ Wszystkie Repository Interfaces w Domain (aktualnie: 0%)
- ✅ Dependency Rule przestrzegana (brak PHPStan violations)

### Funkcjonalność
- ✅ Wszystkie planowane endpointy działają
- ✅ Frontend ma pełną integrację z API
- ✅ CQRS dla wszystkich write operations
- ✅ Event handlers dla wszystkich domain events

---

## 🚀 Rekomendacje Dodatkowe

### 1. PHPStan - Enforcement DDD Rules

Dodać do `phpstan.neon`:

```neon
parameters:
    level: 8
    paths:
        - src
    
    # DDD Rules
    ignoreErrors:
        # Domain nie może zależeć od Application/Infrastructure
        - '#App\\Domain\\.*# uses #App\\Application\\.*#'
        - '#App\\Domain\\.*# uses #App\\Infrastructure\\.*#'
        
        # Application nie może zależeć od Infrastructure (tylko interfejsy)
        - '#App\\Application\\.*# uses #App\\Infrastructure\\.*(?!Interface)#'
    
    # Check for fat models
    reportUnmatchedIgnoredErrors: true
```

### 2. CI/CD Pipeline

Dodać do `.github/workflows/ci.yml`:

```yaml
name: CI

on: [push, pull_request]

jobs:
  tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
          
      - name: Install dependencies
        run: cd backend && composer install
        
      - name: PHPStan Analysis
        run: cd backend && vendor/bin/phpstan analyse
        
      - name: Run Tests
        run: cd backend && vendor/bin/phpunit
        
      - name: Check Architecture
        run: cd backend && vendor/bin/deptrac
```

### 3. Architecture Decision Records (ADR)

Utworzyć `docs/adr/`:

**ADR-001: Adopcja DDD i CQRS**
- Dlaczego: Separacja odpowiedzialności, skalowalność
- Konsekwencje: Więcej kodu, ale lepsze utrzymanie

**ADR-002: Value Objects zamiast prymitywów**
- Dlaczego: Hermetyzacja walidacji, immutability
- Konsekwencje: Większa złożoność Doctrine mapping

**ADR-003: API Platform State Providers/Processors**
- Dlaczego: Separacja prezentacji od domeny
- Konsekwencje: Mniej automatyzacji API Platform

### 4. Monitoring i Observability

```php
// backend/src/Infrastructure/Monitoring/PerformanceMonitor.php
namespace App\Infrastructure\Monitoring;

use Psr\Log\LoggerInterface;

final class PerformanceMonitor
{
    public function __construct(private LoggerInterface $logger) {}

    public function measureCommandExecution(string $commandName, callable $callback): mixed
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        try {
            $result = $callback();
            
            $this->logger->info('Command executed', [
                'command' => $commandName,
                'duration_ms' => (microtime(true) - $startTime) * 1000,
                'memory_mb' => (memory_get_usage() - $startMemory) / 1024 / 1024
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Command failed', [
                'command' => $commandName,
                'error' => $e->getMessage(),
                'duration_ms' => (microtime(true) - $startTime) * 1000
            ]);
            throw $e;
        }
    }
}
```

---

## 📊 Podsumowanie Gap Analysis

### Stan Obecny vs Plan DDD

| Obszar | Wymagane (Plan) | Obecny Stan | % Ukończenia | Priorytet |
|--------|-----------------|-------------|--------------|-----------|
| **Infrastructure Layer** | | | |
| Cache Abstractions | ✅ | ✅ Pełne | 100% | - |
| Email Abstractions | ✅ | ✅ Pełne | 100% | - |
| Payment Gateway | ✅ | ✅ Pełne | 100% | - |
| Messaging | ✅ | ✅ Pełne | 100% | - |
| Repository Interfaces | ✅ | ❌ Brak | 0% | 🔴 HIGH |
| **Domain Layer** | | | |
| Value Objects | ✅ | ❌ Brak | 0% | 🔴 HIGH |
| Agregaty | ✅ | ❌ Brak | 0% | 🔴 HIGH |
| Domain Services | ✅ | ⚠️ 6/10 | 60% | 🟡 MEDIUM |
| Domain Events | ✅ | ⚠️ Częściowo | 40% | 🟡 MEDIUM |
| Domain Exceptions | ✅ | ⚠️ Częściowo | 30% | 🟢 LOW |
| **Application Layer** | | | |
| Application Services | ✅ | ✅ 5/5 | 100% | - |
| CQRS Commands | ✅ | ⚠️ 6 (2 bez handlerów) | 67% | 🟡 MEDIUM |
| CQRS Queries | ✅ | ⚠️ 2 | 20% | 🟡 MEDIUM |
| Command Handlers | ✅ | ⚠️ 4/6 | 67% | 🔴 HIGH |
| Query Handlers | ✅ | ❌ 1 | 10% | 🟡 MEDIUM |
| Event Handlers | ✅ | ⚠️ 3 | 40% | 🟡 MEDIUM |
| **Presentation Layer** | | | |
| DTO Input | ✅ | ⚠️ 3 DTOs | 40% | 🟡 MEDIUM |
| Presenters/ViewModels | ✅ | ❌ Brak | 0% | 🔴 HIGH |
| API Platform Providers | ✅ | ❌ Brak | 0% | 🔴 HIGH |
| API Platform Processors | ✅ | ⚠️ 1 | 10% | 🔴 HIGH |
| **API Endpoints** | | | |
| Auth Endpoints | ✅ 3 | ✅ 3 | 100% | - |
| Event Endpoints | ✅ 6 | ⚠️ 4 | 67% | 🔴 HIGH |
| Ticket Endpoints | ✅ 4 | ⚠️ 1 | 25% | 🔴 HIGH |
| **Legacy Code** | | | |
| Legacy Services | ❌ Do usunięcia | ⚠️ 12 plików | -60% | 🔴 HIGH |
| Duplikacja kodu | ❌ 0% | ⚠️ ~60% | -60% | 🔴 HIGH |
| **Testy** | | | |
| Unit Tests | ✅ >70% | ❌ Brak | 0% | 🟡 MEDIUM |
| Integration Tests | ✅ >50% | ❌ Brak | 0% | 🟡 MEDIUM |

**Ogólny postęp: ~45% ukończenia planu DDD**

---

## 🎬 Natychmiastowe Akcje (Quick Wins)

### Tydzień 1: Brakujące Endpointy (5 dni roboczych)

```php
// 1. POST /api/tickets/purchase
// Wykorzystać istniejący PurchaseTicketHandler
// Szacowany czas: 4 godziny

// 2. GET /api/tickets/my  
// Wykorzystać TicketApplicationService::getUserTickets()
// Szacowany czas: 2 godziny

// 3. POST /api/tickets/{id}/cancel
// Wykorzystać TicketApplicationService::cancelTicket()
// Szacowany czas: 3 godziny

// 4. POST /api/events/{id}/cancel
// UTWORZYĆ: CancelEventHandler
// Szacowany czas: 6 godzin

// 5. GET /api/events/{id}/statistics
// Wykorzystać GetEventStatisticsHandler
// Szacowany czas: 3 godziny

// Total: ~18 godzin = 2-3 dni pracy
```

### Tydzień 2: Usunięcie Legacy Services (5 dni roboczych)

**Kolejność:**
1. **TicketService** → `TicketApplicationService` (4h)
2. **PaymentService** → `PaymentApplicationService` (6h)
3. **EventService** → usunąć (nieużywany) (1h)
4. **AuthService** → usunąć (nieużywany) (1h)
5. **NotificationService** → `NotificationApplicationService` (4h)
6. **EmailService** → usunąć (zastąpiony) (1h)
7. **EventStatisticsService** → rozwiązać konflikt (4h)

**Total: ~21 godzin = 3 dni pracy**

### Efekt po 2 tygodniach:
- ✅ Wszystkie kluczowe endpointy działają
- ✅ 0% duplikacji w Service layer
- ✅ Frontend może korzystać z pełnej funkcjonalności
- ✅ Kod czystszy i łatwiejszy w utrzymaniu

---

## ⚠️ Ryzyka i Mitygacje

### Ryzyko 1: Zbyt ambitny refactoring
**Prawdopodobieństwo:** Wysokie  
**Wpływ:** Krytyczny  
**Mitygacja:** 
- Podział na małe, incrementalne zmiany
- Priorytetyzacja Quick Wins
- Feature flags dla nowej architektury
- Równoległa praca starego i nowego kodu

### Ryzyko 2: Breaking changes w API
**Prawdopodobieństwo:** Średnie  
**Wpływ:** Wysoki  
**Mitygacja:**
- Versioning API (/api/v1, /api/v2)
- Deprecated endpoints z warning
- Migration guide dla frontend
- Backward compatibility przez 2 sprinty

### Ryzyko 3: Brak testów dla refaktoringu
**Prawdopodobieństwo:** Wysokie  
**Wpływ:** Krytyczny  
**Mitygacja:**
- Najpierw napisać testy dla obecnego kodu
- Test-first approach dla nowych komponentów
- Integration tests dla krytycznych ścieżek
- Smoke tests w CI/CD

### Ryzyko 4: Overengineering
**Prawdopodobieństwo:** Średnie  
**Wpływ:** Średni  
**Mitygacja:**
- YAGNI principle - nie implementować "na przyszłość"
- Code review z zespołem
- Pragmatyczne podejście do DDD
- Simplicity over perfection

### Ryzyko 5: Performance degradation
**Prawdopodobieństwo:** Niskie  
**Wpływ:** Wysoki  
**Mitygacja:**
- Performance tests przed i po zmianach
- Profiling krytycznych endpointów
- Cache strategy review
- Load testing

---

## 📝 Konkluzje i Rekomendacje Finalne

### 🎯 Kluczowe Wnioski

1. **Projekt jest w ~45% zgodności z planem DDD**
   - Dobra podstawa infrastruktury (Cache, Email, Payment)
   - Application Services są gotowe, ale niewykorzystane
   - Brak kluczowych elementów: Value Objects, Agregaty, Presenters

2. **Masywna duplikacja kodu (~60%)**
   - 12 legacy services do usunięcia
   - 2 wersje EventStatisticsService (konflikt!)
   - Kontrolery używają mieszanki legacy i nowych services

3. **Brakujące funkcjonalności API**
   - 5 kluczowych endpointów nie istnieje
   - Handlery gotowe, ale brak kontrolerów
   - Frontend czeka na te endpointy

4. **Naruszenia zasad DDD**
   - Encje wyciekają do API responses
   - Brak Value Objects
   - Brak agregatów
   - Repository używane bezpośrednio

### 🚀 Najważniejsze Rekomendacje

#### 🔴 PRIORYTET 1 (Krytyczny - 2 tygodnie)
1. **Dokończyć brakujące endpointy** (3-5 dni)
   - POST /api/tickets/purchase
   - GET /api/tickets/my
   - POST /api/tickets/{id}/cancel
   - POST /api/events/{id}/cancel
   - GET /api/events/{id}/statistics

2. **Usunąć legacy services** (5-7 dni)
   - Migrować TicketController
   - Migrować PaymentHandler
   - Usunąć 8 legacy services
   - Rozwiązać konflikt EventStatisticsService

**Efekt:** Czysta architektura, brak duplikacji, pełna funkcjonalność API

#### 🟡 PRIORYTET 2 (Wysoki - 2-3 tygodnie)
3. **Implementować Value Objects** (5-7 dni)
   - Money, Currency, Email, EventDate
   - TicketStatus, PaymentStatus, EventStatus
   - Migracja encji

4. **Implementować Agregaty** (7-10 dni)
   - EventAggregate
   - OrderAggregate
   - Repository Interfaces

**Efekt:** Prawdziwe DDD, biznes logic w Domain layer

#### 🟢 PRIORYTET 3 (Średni - 1-2 tygodnie)
5. **Implementować Presenters** (4-5 dni)
   - EventPresenter, TicketPresenter, UserPresenter

6. **API Platform Providers/Processors** (5-7 dni)
   - State Providers dla GET
   - State Processors dla POST/PUT

**Efekt:** Separacja prezentacji od domeny, czyste API

### 📊 Roadmap do Sukcesu

```
TYDZIEŃ 1-2:   Quick Wins (endpointy + usunięcie legacy)
               ↓ 
TYDZIEŃ 3-5:   Value Objects + Agregaty
               ↓
TYDZIEŃ 6-7:   Presenters + API Platform
               ↓
TYDZIEŃ 8-9:   Domain Events + Handlers
               ↓
TYDZIEŃ 10-12: Testy + Dokumentacja
               ↓
               🎉 100% DDD COMPLIANCE
```

### 💡 Najważniejsza Sugestia

**Zacznij od Quick Wins (Priorytet 1)** - w 2 tygodnie możesz mieć:
- ✅ Wszystkie endpointy działające
- ✅ 0% duplikacji kodu
- ✅ Czystą architekturę
- ✅ Zadowolony frontend team

**Potem iteracyjnie dodawaj DDD patterns** - nie próbuj zrobić wszystkiego na raz!

---

## 📚 Przydatne Zasoby

### Książki
- **"Domain-Driven Design"** - Eric Evans (biblia DDD)
- **"Implementing Domain-Driven Design"** - Vaughn Vernon
- **"Domain-Driven Design in PHP"** - Carlos Buenosvinos

### Artykuły
- [DDD, Hexagonal, Onion, Clean, CQRS](https://herbertograca.com/2017/11/16/explicit-architecture-01-ddd-hexagonal-onion-clean-cqrs-how-i-put-it-all-together/)
- [Symfony + DDD Best Practices](https://www.symfony.com/doc/current/best_practices.html)
- [API Platform + DDD](https://api-platform.com/docs/core/state-processors/)

### Projekty Open Source
- [Symfony Demo Application DDD](https://github.com/jorge07/ddd-playground)
- [Clean Architecture PHP](https://github.com/realpablo/clean-architecture-php)

---

**Dokument przygotowany:** 2025-01-29  
**Wersja:** 1.0  
**Autor:** AI Analysis  
**Status:** Gotowy do implementacji

---

## 🤝 Co dalej?

Teraz najlepiej byłoby:

1. **Przejrzeć ten dokument** z zespołem
2. **Zdecydować o priorytecie** - czy Quick Wins (2 tyg) czy full DDD (12 tyg)?
3. **Utworzyć Jira tickets** dla każdego sprintu
4. **Zacząć od Sprintu 1** (Quick Wins)

Czy chcesz, żebym:
- Utworzył szczegółowe Jira tickets dla Sprintu 1?
- Zaczął implementować Quick Wins (brakujące endpointy)?
- Przygotował szczegółowy Architecture Decision Record?
- Coś innego?
