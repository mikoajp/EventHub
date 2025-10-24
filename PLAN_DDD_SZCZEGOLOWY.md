# 📐 Plan DDD/Refactor - Szczegółowa Analiza i Doprecyzowanie

## 🎯 Cel Nadrzędny

Transformacja EventHub z hybrydy legacy/DDD do czystej architektury warstwowej z pełną separacją odpowiedzialności, bez zmian w kodzie biznesowym na poziomie funkcjonalności.

---

## Faza 0: Ustalenie Architektury Docelowej i Reguł

### 📋 Zakres

Skrót Fazy 0 (TL;DR):
- Warstwy: Domain (Entities, ValueObjects, Domain Services, Events), Application (Commands/Queries + Handlers, Transactional), Infrastructure (ORM, Cache, Bus, Mercure, HTTP), Interface/Presentation (Controllers, API Platform Providers/State Processors, Presenters, DTOs)
- Zasady: brak prezentacji w Domain; Entities bez "toArray/*Formatted"; Request walidacja w DTO; odpowiedzi przez DTO/ViewModel/Presenter; zależności w dół (Interface->App->Domain), zero zależności w górę; Cache/tagi tylko poza Domain
- Narzędzia/prowadnice: Deptrac (granice warstw), PHPStan (level wysoki), Rector (mechaniczne przenosiny), PHPCS, CI broni reguł; ADR opis decyzji

**Docelowa struktura katalogów:**
```
src/
├── Domain/                          # Czysta logika biznesowa
│   ├── Event/
│   │   ├── Entity/
│   │   │   └── Event.php           # TYLKO gettery/settery + proste computed
│   │   ├── ValueObject/
│   │   │   ├── EventStatus.php     # Enum/VO zamiast stringów
│   │   │   ├── Money.php           # Price jako VO
│   │   │   └── EventDate.php       # Walidacja dat
│   │   ├── Service/
│   │   │   ├── EventDomainService.php
│   │   │   └── EventPublishingService.php
│   │   ├── Event/                  # Domain Events
│   │   │   ├── EventWasPublished.php
│   │   │   └── EventWasCancelled.php
│   │   └── Repository/
│   │       └── EventRepositoryInterface.php  # Interface w Domain
│   ├── Ticket/
│   ├── User/
│   └── Shared/                     # Shared Kernel
│       ├── ValueObject/
│       │   ├── Email.php
│       │   ├── Money.php
│       │   └── Uuid.php
│       └── Exception/
│
├── Application/                     # Use Cases & Orchestration
│   ├── Command/
│   │   ├── Event/
│   │   │   ├── CreateEventCommand.php        # Command DTO
│   │   │   ├── CreateEventHandler.php        # @Transactional
│   │   │   ├── PublishEventCommand.php
│   │   │   └── PublishEventHandler.php
│   │   └── Ticket/
│   ├── Query/
│   │   ├── Event/
│   │   │   ├── GetEventDetailsQuery.php
│   │   │   ├── GetEventDetailsHandler.php
│   │   │   ├── ListEventsQuery.php
│   │   │   └── ListEventsHandler.php
│   │   └── Ticket/
│   └── Service/
│       └── EventCacheService.php   # Application-level caching
│
├── Infrastructure/                  # Technical Capabilities
│   ├── Persistence/
│   │   ├── Doctrine/
│   │   │   ├── Repository/
│   │   │   │   └── DoctrineEventRepository.php  # Implements Domain Interface
│   │   │   └── Type/
│   │   │       └── MoneyType.php   # Custom Doctrine types
│   │   └── Migration/
│   ├── Cache/
│   │   ├── RedisCacheAdapter.php
│   │   └── CacheTagManager.php
│   ├── Messaging/
│   │   ├── SymfonyMessengerBus.php
│   │   └── MercurePublisher.php
│   ├── Email/
│   │   └── SymfonyMailerAdapter.php
│   └── Payment/
│       └── StripePaymentGateway.php
│
└── Presentation/                    # User Interface Layer
    ├── Api/
    │   ├── Controller/
    │   │   ├── Event/
    │   │   │   └── EventController.php       # Thin controllers
    │   │   └── Ticket/
    │   ├── Request/                          # Input DTOs
    │   │   ├── CreateEventRequest.php
    │   │   └── PurchaseTicketRequest.php
    │   ├── Response/                         # Output DTOs
    │   │   ├── EventListItemResponse.php
    │   │   ├── EventDetailsResponse.php
    │   │   └── TicketResponse.php
    │   └── Presenter/
    │       ├── EventPresenter.php            # Domain → Response DTO
    │       └── TicketPresenter.php
    ├── ApiPlatform/
    │   ├── StateProvider/
    │   │   └── EventProvider.php
    │   └── StateProcessor/
    │       └── EventProcessor.php
    └── Validation/
        └── Constraint/
            └── UniqueEmail.php
```

### 🎯 Zasady Architektoniczne (Architectural Rules)

#### 1. Dependency Rule (Reguła Zależności)
```
Presentation → Application → Domain
Infrastructure → Application/Domain

❌ NIGDY: Domain → Application
❌ NIGDY: Domain → Infrastructure  
❌ NIGDY: Domain → Presentation
```

#### 2. Entity Rules (Reguły Encji)
```php
// ✅ DOZWOLONE w Entity:
class Event {
    private Uuid $id;
    private string $name;
    private EventStatus $status;  // Value Object
    
    public function getId(): Uuid { return $this->id; }
    public function getName(): string { return $this->name; }
    
    // Proste computed properties (bez zależności zewnętrznych)
    public function getAvailableTickets(): int {
        return $this->maxTickets - $this->soldTickets;
    }
    
    // Domain logic (business rules)
    public function publish(): void {
        if (!$this->status->isDraft()) {
            throw new EventAlreadyPublishedException();
        }
        $this->status = EventStatus::published();
        $this->publishedAt = new \DateTimeImmutable();
    }
}

// ❌ ZABRONIONE w Entity:
class Event {
    // ❌ Formatowanie
    public function getFormattedDate(): string { ... }
    public function getStatusLabel(): string { ... }
    
    // ❌ Serializacja
    public function toArray(): array { ... }
    public function jsonSerialize(): array { ... }
    
    // ❌ Prezentacja
    #[Groups(['event:read'])]
    private string $password; // NIGDY!
    
    // ❌ Logika aplikacyjna (cache, messaging)
    public function notifySubscribers() { ... }
}
```

#### 3. Request/Response Rules
```php
// ✅ Controller:
class EventController {
    public function create(
        CreateEventRequest $request,      // Input DTO z walidacją
        CreateEventHandler $handler,
        EventPresenter $presenter
    ): JsonResponse {
        $command = new CreateEventCommand(
            $request->name,
            $request->description,
            // ...
        );
        
        $event = $handler->handle($command);
        
        return new JsonResponse(
            $presenter->present($event),    // Output DTO
            Response::HTTP_CREATED
        );
    }
}

// ❌ NIGDY nie zwracaj Entity bezpośrednio:
return $this->json($event);  // ❌ Ekspozycja wszystkich pól!
```

#### 4. Cache Rules
```php
// ✅ Cache TYLKO w Application/Infrastructure:
class EventCacheService {
    public function getEvent(Uuid $id): ?Event {
        return $this->cache->get(
            "event:{$id}",
            fn() => $this->repository->find($id),
            3600,
            ['events', "event:{$id}"]  // Tagi
        );
    }
    
    public function invalidateEvent(Uuid $id): void {
        $this->cache->invalidateTags(["event:{$id}"]);
    }
}

// ❌ NIE w Domain Services:
class EventDomainService {
    public function createEvent(...): Event {
        $event = new Event(...);
        $this->cache->invalidate(...);  // ❌ Domain nie zna cache!
    }
}
```

### 📐 Narzędzia i Konfiguracja

#### Deptrac Configuration
```yaml
# deptrac.yaml
paths:
  - ./src
layers:
  - name: Domain
    collectors:
      - type: directory
        regex: src/Domain/.*
  - name: Application
    collectors:
      - type: directory
        regex: src/Application/.*
  - name: Infrastructure
    collectors:
      - type: directory
        regex: src/Infrastructure/.*
  - name: Presentation
    collectors:
      - type: directory
        regex: src/Presentation/.*

ruleset:
  Domain: ~  # Domain nie zależy od niczego
  Application:
    - Domain
  Infrastructure:
    - Application
    - Domain
  Presentation:
    - Application
    - Domain  # Tylko przez DTO
```

#### PHPStan Configuration
```neon
# phpstan.neon
parameters:
    level: 8  # Maximum strictness
    paths:
        - src
    
    ignoreErrors:
        # Temporary during migration
        - '#.*Service.*#'  # Legacy services
    
    # Custom rules
    rules:
        - App\Quality\Rule\NoEntityInResponseRule
        - App\Quality\Rule\NoFormattingInEntityRule
        - App\Quality\Rule\NoDependencyInversionRule
```

#### PHPStan Custom Rules
```php
// src/Quality/Rule/NoEntityInResponseRule.php
class NoEntityInResponseRule implements Rule {
    public function getNodeType(): string {
        return MethodCall::class;
    }
    
    public function processNode(Node $node, Scope $scope): array {
        // Sprawdź czy kontroler zwraca Entity zamiast DTO
        // Jeśli tak → error
    }
}
```

#### Rector Configuration
```php
// rector.php
use Rector\Config\RectorConfig;

return static function (RectorConfig $config): void {
    $config->paths([__DIR__ . '/src']);
    
    // Custom rules dla migracji
    $config->rule(RemoveFormattingFromEntityRule::class);
    $config->rule(RemoveToArrayFromEntityRule::class);
    $config->rule(RemoveSerializationGroupsRule::class);
};
```

### 📝 ADR Template (Architecture Decision Record)

```markdown
# ADR-001: Separation of Presentation from Domain

## Status
Accepted

## Context
Entity classes contained formatting methods (getFormattedDate, toArray) mixing 
domain logic with presentation concerns.

## Decision
- Remove all formatting from Entities
- Introduce Presenter layer in Presentation namespace
- Use Response DTOs for all API outputs
- Entities never serialized directly

## Consequences
+ Clear separation of concerns
+ Easier testing (presenters testable independently)
+ Flexibility in API versioning (multiple presenters per entity)
- Initial effort to create presenters
- More classes (but simpler ones)

## Implementation
- Phase 1: Create EventPresenter
- Phase 2: Update EventController to use presenter
- Phase 3: Remove formatting methods from Event entity
```

### ✅ Deliverables Fazy 0

- [ ] Dokument architektury docelowej (ten plik)
- [ ] Struktura katalogów (pusta, ready for migration)
- [ ] Deptrac configuration z regułami
- [ ] PHPStan level 8 + custom rules
- [ ] Rector configuration
- [ ] PHPCS/PHP-CS-Fixer rules
- [ ] Template ADR
- [ ] CI pipeline configuration (wstępna)

### 🎯 KPI Fazy 0

- ✅ Dokument zaakceptowany przez zespół
- ✅ Deptrac/PHPStan/Rector skonfigurowane
- ✅ Wszyscy wiedzą: "gdzie co idzie"
- ✅ Zero kodu migrowanego (tylko setup)

### 🔎 Faza 0 – Readiness check (2025-10-23T17:05:38Z)

Status narzędzi i plików:
- deptrac.yaml: Missing
- phpstan.neon(.dist): Missing
- rector.php: Missing
- Coding standards (.php-cs-fixer.php lub phpcs.xml): Missing
- CI (.github/workflows/*.yml): Missing
- ADR folder (docs/adr lub adr/): Missing
- Symfony configi istotne na starcie: Present (backend/config/packages: lexik_jwt_authentication.yaml, mercure.yaml, messenger.yaml, cache.yaml)

Działania wymagane, aby domknąć Fazę 0 teraz:
- Utwórz bazowy deptrac.yaml wg sekcji „Narzędzia i Konfiguracja” powyżej (reguły warstw).
- Dodaj phpstan.neon z level: 8 oraz ścieżką src/ (+ tymczasowe ignore dla legacy Service/*).
- Dodaj rector.php (bez ryzykownych refaktorów na start; reguły usuwające toArray/*Formatted później, za feature‑flagą).
- Dodaj konfigurację CS (.php-cs-fixer.php z PSR-12 lub phpcs.xml) i lokalny/CI krok lintowania.
- Skonfiguruj CI: .github/workflows/ci.yml (composer validate, cs --dry-run, phpstan, deptrac, phpunit).
- Utwórz katalog docs/adr i pierwszy ADR wg szablonu (ADR-001 Separation of Presentation from Domain).

Wniosek: Faza 0 nie jest jeszcze kompletna (brak plików narzędzi/CI); po dodaniu powyższych można bezpiecznie rozpocząć Fazę 1 (audyt) równolegle z utrzymaniem zielonego CI.

### ⏱️ Estymacja

**Czas:** 1 dzień  
**Uczestnicy:** Tech Lead + Senior Dev  
**Output:** Dokumentacja + konfiguracja narzędzi

---

## Faza 1: Audyt kodu i inwentaryzacja miejsc do refaktoru

### Zakres
- Przegląd: Entity/*, ValueObject/*, Service/*, Controller/*, Serializer/Normalizer, ApiPlatform Provider/Processor, Infrastructure/*.
- Identyfikacja: metod prezentacyjnych w encjach (toArray, getFormatted*, Groups do odczytu), logiki orchestracji w Service/*, bezpośredniej serializacji encji.
- Mapa migracji: prezentacja → Presenter/Response DTO; orchestracja → Command/Query + Handler; dostęp do danych → Repo/Query.

### Deliverables
- [ ] Lista refaktorów per plik z przydziałem (owner, estymacja)
- [ ] Tabela ryzyk/ zależności (co blokuje co)
- [ ] Lista endpointów zwracających encje bezpośrednio

### KPI
- ✅ 100% encji skatalogowane pod kątem prezentacji
- ✅ Spisana mapa „stare → nowe” dla każdego modułu

#### Checklist – Audyt: kroki i artefakty
- Krok 1: Przeskanuj encje pod metody prezentacyjne (toArray, get*Formatted), atrybuty #[Groups(...:read)] i wrażliwe pola w read (password, tokens). Artefakt: docs/audit/findings.md
- Krok 2: Zidentyfikuj miejsca orchestracji w src/Service oraz bezpośrednią serializację encji w Controllerach/ApiPlatform. Artefakt: docs/audit/inventory.md
- Krok 3: Dla każdego pliku opisz „stare → nowe”: Presenter/DTO, Command/Query + Handler, Repo/Query. Artefakt: docs/audit/inventory.csv
- Krok 4: Oznacz właściciela, poziom ryzyka, ETA; potwierdź z zespołem priorytety.
- Krok 5: Zatwierdź Definition of Done i utwórz zadania per moduł (Users, Events, Tickets, Orders, Notifications).

#### Szablon inwentaryzacji (MD)
| module | file | issue | proposed DTO/Presenter | migration target | owner | ETA | risk | notes |
|-------|------|-------|------------------------|------------------|-------|-----|------|-------|
| Users | src/Domain/User/Entity/User.php | password in read group | UserPublicDTO/UserPresenter | Presenter + remove read group | jan | 1d | M | — |

#### Przykładowe komendy skanów
- ripgrep: rg -n "(toArray|getFormatted|#\[Groups\(.*:read)" src
- PowerShell: Get-ChildItem -Recurse src | Select-String -Pattern "toArray|getFormatted|#\[Groups\(.*:read)"
- PHPStan: vendor/bin/phpstan analyse src --level=max
- Deptrac: vendor/bin/deptrac --config=deptrac.yaml

#### Definition of Done – Faza 1
- 100% encji i endpointów zewidencjonowane w docs/audit (MD+CSV).
- Lista problemów + „stare → nowe” uzgodniona z zespołem i zpriorytetyzowana.
- Brak zmian kodu produkcyjnego; tylko dokumentacja i backlog zadań (tickets z owner/ETA).

#### Uwagi/Braki
1. Brak Przykładu Wypełnionego Inventory
   Timestamp: 2025-10-23T17:18:06.788Z

---

## Faza 2: Kontrakty DTO/Presenterów per agregat

#### Kontrakty DTO/Presenter – Ticket/Order (2025-10-24T12:50:56Z)
- Ticket DTO: TicketOutput {id,status,price,priceFormatted,createdAt,purchasedAt?,qrCode?,event{id,name,eventDate,venue},ticketType{id,name}}
- TicketPresenter: presentUserTickets mapuje Entity/array → DTO i formatuje Money/daty.
- Order DTO: OrderOutput {id,status,totalAmount,totalAmountFormatted,createdAt,updatedAt?,items[]}
- OrderPresenter: presentSummary/presentDetails używa Money::format.

Przykładowe JSON:
Ticket:
{
  "id": "t_123",
  "status": "purchased",
  "price": 1500,
  "priceFormatted": "15.00 PLN",
  "createdAt": "2025-10-24T12:00:00Z",
  "purchasedAt": "2025-10-24T12:05:00Z",
  "event": {"id": "e_1", "name": "Conf", "eventDate": "2025-12-01T09:00:00Z", "venue": "Main"},
  "ticketType": {"id": "tt_1", "name": "Standard"}
}

Order:
{
  "id": "o_1",
  "status": "paid",
  "totalAmount": 3000,
  "totalAmountFormatted": "30.00 PLN",
  "createdAt": "2025-10-24T12:06:00Z",
  "items": [
    {"ticketType": {"id": "tt_1", "name": "Standard"}, "quantity": 2, "unitPrice": 1500, "unitPriceFormatted": "15.00 PLN", "totalPrice": 3000, "totalPriceFormatted": "30.00 PLN"}
  ]
}

### Zakres
- Zdefiniuj Request DTO (wejście), Response/ViewModel DTO (wyjście) i Presentery dla: Event, Ticket, Order, User.
- Zasady: brak logiki biznesowej w DTO; formatowanie i mapowanie tylko w Presenterach.

### Deliverables
- [ ] Specyfikacje DTO (pola, typy, przykładowy JSON)
- [ ] Interfejsy Presenterów (np. EventPresenterInterface)
- [ ] Konwencje: nazewnictwo i ścieżki (Presentation/Api/{Request|Response|Presenter}), formaty (ISO8601 UTC, Money amount+currency), paginacja, ErrorDTO
- [ ] Szablony plików: Request DTO, Response DTO, PresenterInterface + przykładowy Presenter mapujący Entity→DTO
- [ ] Przykładowe JSON/Schema dla Users, Events (snapshoty kontraktów)

#### Przykładowe DTO/Presenter – Users (2025-10-23T17:30:15.416Z)
- Request DTO (create): Presentation/Api/Request/User/CreateUserRequest.php
  - fields: email:string (NotBlank, Email), password:string (Length>=8)
- Response DTO (public): Presentation/Api/Response/User/UserPublicDTO.php
  - fields: id:string uuid, email:string, createdAt:string(ISO8601)
- Presenter: Presentation/Api/Presenter/User/UserPresenter.php
  - present(User $user): UserPublicDTO maps id, email, createdAt->format(DATE_ATOM)

Przykładowy JSON (UserPublicDTO):
{
  "id": "6f7b3c2a-1d9c-4b7b-9a31-5c2a9c8f4e21",
  "email": "jane.doe@example.com",
  "createdAt": "2025-10-23T12:00:00Z"
}


### KPI
- ✅ Co najmniej 1 komplet DTO/Presenter dla modułu Users i Events zaakceptowany
- ✅ Zatwierdzone konwencje i szablony; sample JSON/Schema opublikowane (2025-10-23T17:28:22.662Z)

#### Kompletny przykład end-to-end – Users: Register (2025-10-23T20:09:36.777Z)

1) Application/Command/User/RegisterUserCommand.php
```php
<?php
namespace App\Application\Command\User;
final class RegisterUserCommand { public function __construct(public string $email, public string $password) {} }
```

2) Application/Command/User/RegisterUserHandler.php
```php
<?php
namespace App\Application\Command\User;
use App\Domain\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler]
final class RegisterUserHandler {
  public function __construct(private EntityManagerInterface $em, private UserPasswordHasherInterface $hasher) {}
  public function __invoke(RegisterUserCommand $c): string {
    $user = new User($c->email);
    $user->setPassword($this->hasher->hashPassword($user, $c->password));
    $this->em->persist($user); $this->em->flush();
    return (string) $user->getId();
  }
}
```

3) Presentation/Api/Request/Auth/RegisterRequest.php
```php
<?php
namespace App\Presentation\Api\Request\Auth;
use Symfony\Component\Validator\Constraints as Assert; use Symfony\Component\Serializer\Annotation\Groups;
final class RegisterRequest {
  #[Assert\NotBlank(groups:['register'])] #[Assert\Email(groups:['register'])] #[Groups(['register'])] public string $email;
  #[Assert\NotBlank(groups:['register'])] #[Assert\Length(min:8, groups:['register'])] #[Groups(['register'])] public string $password;
}
```

4) Presentation/Api/Response/User/UserPublicDTO.php
```php
<?php
namespace App\Presentation\Api\Response\User;
final class UserPublicDTO { public function __construct(public string $id, public string $email, public string $createdAt) {} }
```

5) Presentation/Api/Presenter/User/UserPresenter.php
```php
<?php
namespace App\Presentation\Api\Presenter\User;
use App\Domain\User\Entity\User; use App\Presentation\Api\Response\User\UserPublicDTO;
final class UserPresenter { public function present(User $u): UserPublicDTO { return new UserPublicDTO((string)$u->getId(), $u->getEmail(), $u->getCreatedAt()->format(DATE_ATOM)); } }
```

6) API Platform – Processor: Infrastructure/ApiPlatform/State/User/RegisterUserProcessor.php
```php
<?php
namespace App\Infrastructure\ApiPlatform\State\User;
use ApiPlatform\Metadata\Operation; use ApiPlatform\State\ProcessorInterface;
use App\Application\Command\User\RegisterUserCommand; use App\Presentation\Api\Request\Auth\RegisterRequest;
use App\Presentation\Api\Presenter\User\UserPresenter; use App\Presentation\Api\Response\User\UserPublicDTO;
use Symfony\Component\Messenger\MessageBusInterface; use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
final class RegisterUserProcessor implements ProcessorInterface {
  public function __construct(private MessageBusInterface $bus, private ValidatorInterface $validator, private UserPresenter $presenter) {}
  public function process(mixed $data, Operation $op, array $uv = [], array $ctx = []): UserPublicDTO {
    \assert($data instanceof RegisterRequest);
    $violations = $this->validator->validate($data, groups:['register']); if (count($violations)>0) throw new ValidationFailedException($data,$violations);
    $this->bus->dispatch(new RegisterUserCommand($data->email, $data->password));
    return new UserPublicDTO('pending', $data->email, (new \DateTimeImmutable())->format(DATE_ATOM));
  }
}
```

7) API Resource (POST /api/users)
```php
use ApiPlatform\Metadata\ApiResource; use ApiPlatform\Metadata\Post;
use App\Infrastructure\ApiPlatform\State\User\RegisterUserProcessor; use App\Presentation\Api\Request\Auth\RegisterRequest; use App\Presentation\Api\Response\User\UserPublicDTO;
#[ApiResource(operations:[ new Post(input: RegisterRequest::class, output: UserPublicDTO::class, processor: RegisterUserProcessor::class, validationContext: ['groups'=>['register']]) ])]
final class UserRegisterResource {}
```

8) Tests – skróty
- Unit: tests/Unit/Presentation/UserPresenterTest.php – mapowanie DTO bez hasła
- Functional: tests/Functional/Api/RegisterUserTest.php – POST /api/users 201/202; 422 dla złych danych

### Definition of Done – Users (Register/Login)
- Brak pola password w jakimkolwiek response; DTO: id/email/createdAt
- Walidacje DTO (groups) działają; 422 ErrorDTO dla błędów
- Rejestracja przez Command/Handler; testy unit/functional zielone

---

#### Kompletny przykład – Users: Login (2025-10-23T20:13:24.648Z)

1) Presentation/Api/Request/Auth/LoginRequest.php
```php
<?php
namespace App\Presentation\Api\Request\Auth;
use Symfony\Component\Validator\Constraints as Assert; use Symfony\Component\Serializer\Annotation\Groups;
final class LoginRequest {
  #[Assert\NotBlank(groups:['login'])] #[Assert\Email(groups:['login'])] #[Groups(['login'])] public string $email;
  #[Assert\NotBlank(groups:['login'])] #[Groups(['login'])] public string $password;
}
```

2) Infrastructure/ApiPlatform/State/User/LoginUserProcessor.php
```php
<?php
namespace App\Infrastructure\ApiPlatform\State\User;
use ApiPlatform\Metadata\Operation; use ApiPlatform\State\ProcessorInterface;
use App\Presentation\Api\Request\Auth\LoginRequest; use App\Presentation\Api\Response\User\UserPublicDTO; use App\Presentation\Api\Presenter\User\UserPresenter;
use Doctrine\ORM\EntityManagerInterface; use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface; use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
final class LoginUserProcessor implements ProcessorInterface {
  public function __construct(private EntityManagerInterface $em, private UserPasswordHasherInterface $hasher, private ValidatorInterface $validator, private UserPresenter $presenter) {}
  public function process(mixed $data, Operation $op, array $uv = [], array $ctx = []): UserPublicDTO {
    \assert($data instanceof LoginRequest);
    $viol = $this->validator->validate($data, groups:['login']); if (count($viol)>0) throw new ValidationFailedException($data,$viol);
    $user = $this->em->getRepository(\App\Domain\User\Entity\User::class)->findOneBy(['email'=>$data->email]);
    if (!$user || !$this->hasher->isPasswordValid($user, $data->password)) throw new UnauthorizedHttpException('Bearer','Invalid credentials');
    return $this->presenter->present($user);
  }
}
```

3) API Resource (POST /api/login)
```php
use ApiPlatform\Metadata\ApiResource; use ApiPlatform\Metadata\Post;
use App\Infrastructure\ApiPlatform\State\User\LoginUserProcessor; use App\Presentation\Api\Request\Auth\LoginRequest; use App\Presentation\Api\Response\User\UserPublicDTO;
#[ApiResource(operations:[ new Post(input: LoginRequest::class, output: UserPublicDTO::class, processor: LoginUserProcessor::class, validationContext: ['groups'=>['login']]) ])]
final class UserLoginResource {}
```

#### API Platform Provider – Users:get (2025-10-23T20:13:24.648Z)

1) Infrastructure/ApiPlatform/State/User/UserGetProvider.php
```php
<?php
namespace App\Infrastructure\ApiPlatform\State\User;
use ApiPlatform\Metadata\Operation; use ApiPlatform\State\ProviderInterface;
use App\Presentation\Api\Presenter\User\UserPresenter; use Doctrine\ORM\EntityManagerInterface; use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
final class UserGetProvider implements ProviderInterface {
  public function __construct(private EntityManagerInterface $em, private UserPresenter $presenter) {}
  public function provide(Operation $op, array $uv = [], array $ctx = []): mixed {
    $id = $uv['id'] ?? null; if (!$id) throw new NotFoundHttpException('User id required');
    $user = $this->em->getRepository(\App\Domain\User\Entity\User::class)->find($id);
    if (!$user) throw new NotFoundHttpException('User not found');
    return $this->presenter->present($user);
  }
}
```

2) API Resource (GET /api/users/{id})
```php
use ApiPlatform\Metadata\ApiResource; use ApiPlatform\Metadata\Get; use App\Infrastructure\ApiPlatform\State\User\UserGetProvider;
use App\Presentation\Api\Response\User\UserPublicDTO;
#[ApiResource(operations:[ new Get(provider: UserGetProvider::class) ])]
final class UserGetResource extends UserPublicDTO {}
```

#### API Platform Provider – Users:list (GetCollection) – 2025-10-23T20:15:26.662Z

1) Infrastructure/ApiPlatform/State/User/UserListProvider.php
```php
<?php
namespace App\Infrastructure\ApiPlatform\State\User;
use ApiPlatform\Metadata\Operation; use ApiPlatform\State\ProviderInterface;
use App\Presentation\Api\Presenter\User\UserPresenter; use Doctrine\ORM\EntityManagerInterface;
final class UserListProvider implements ProviderInterface {
  public function __construct(private EntityManagerInterface $em, private UserPresenter $presenter) {}
  public function provide(Operation $op, array $uv = [], array $ctx = []): iterable {
    $page = max(1, (int)($ctx['filters']['page'] ?? 1));
    $perPage = min(100, (int)($ctx['filters']['itemsPerPage'] ?? 10));
    $offset = ($page - 1) * $perPage;
    $repo = $this->em->getRepository(\App\Domain\User\Entity\User::class);
    $users = $repo->findBy([], ['createdAt' => 'DESC'], $perPage, $offset);
    return array_map(fn($u) => $this->presenter->present($u), $users);
  }
}
```

2) API Resource (GET /api/users)
```php
use ApiPlatform\Metadata\ApiResource; use ApiPlatform\Metadata\GetCollection;
use App\Infrastructure\ApiPlatform\State\User\UserListProvider; use App\Presentation\Api\Response\User\UserPublicDTO;
#[ApiResource(operations:[ new GetCollection(provider: UserListProvider::class, paginationEnabled: true, paginationItemsPerPage: 10) ])]
final class UserListResource extends UserPublicDTO {}
```

## Faza 3: Warstwa Application – use‑case’y, transakcje, messaging, cache

### Zakres
- Wydziel Command/Query + Handlery, transakcje na Handlerach.
- Standaryzuj messaging na Symfony Messenger; side‑effecty (Mercure, email, cache) poza Domain.
- Cache: TagAware; polityka tagów: 'events', 'event:{id}', 'event:stats:{id}', 'users', 'user:{id}'.

### Deliverables
- [ ] Szkielety CreateEventCommand/Handler, PublishEventCommand/Handler, GetEventDetailsQuery/Handler
- [ ] TagAwareAdapter skonfigurowany i używany w Application
- [ ] Polityka invalidacji (gdzie wywołać invalidateTags)
- [ ] Konwencje: Command/Query/Handler nazewnictwo i ścieżki (Application/{Command|Query}/<BoundedContext>/)
- [ ] Polityka transakcji: @Transactional na Handlerach mutujących (Doctrine) + idempotencja komend
- [ ] Messaging: użycie MessageBusInterface (commandBus/eventBus); routing i retry policy opisane
- [ ] Cache: TagAwareCacheInterface używana w Application; TTL domyślne i tagi per moduł (users, user:{id}, events, event:{id}, event:stats:{id})
- [ ] Invalidacja: mapa mutacji→tagi do invalidateTags (create/update/delete)
- [ ] RT: publikacja zmian przez HubInterface (Mercure) po mutacjach (z Presenterem + DTO payload)

#### Konwencje i polityki – Faza 3 (2025-10-23T17:32:20.605Z)
- Command nazwa: <Verb><Entity>Command; Handler: <Verb><Entity>Handler; Query: <Get/List><Entity><Suffix>Query
- Ścieżki: src/Application/Command/Event/CreateEventCommand.php itd.
- Transakcje: tylko w Handlerach mutujących; Queries bez transakcji
- Messaging: domain events publikowane po flush; retry: 3 próby, dead-letter queue: amqp dlq
- Cache: get z cache, set po cache miss z TTL=600s; tagi jw.
- Invalidacja: CreateEvent→tags: events; UpdateEvent→event:{id}, events; DeleteEvent→event:{id}, events, event:stats:{id}
- RT: topics: "/events/{id}" i "/events"; payload = Response DTO

#### Przykładowe szkielety (pseudokod)
CreateEventCommand { name:string, startsAt:DateTimeImmutable, ... }
CreateEventHandler {
  __construct(Repo, EntityManager, Cache, Hub, Presenter) {}
  __invoke(CreateEventCommand $c) {
    em->transactional(function() use($c) { /* create & persist */ });
    cache->invalidateTags(['events']);
    hub->publish(topic:'/events', data: presenter->present($event));
    return $eventId;
  }
}
GetEventDetailsQuery { id:Uuid }
GetEventDetailsHandler {
  __construct(Repo, Cache, Presenter) {}
  __invoke(GetEventDetailsQuery $q) {
    $event = cache->get("event:{$q->id}", fn()=>repo->get($q->id), ttl:600, tags:['events', "event:{$q->id}"]);
    return presenter->presentDetails($event);
  }
}

#### Przykładowa konfiguracja Messenger (routing i retry) – 2025-10-23T17:34:45.224Z
```yaml
framework:
  messenger:
    failure_transport: failed
    transports:
      async:
        dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
        retry_strategy:
          max_retries: 3
          delay: 2000        # ms
          multiplier: 2
          max_delay: 60000   # ms
      failed:
        dsn: '%env(MESSENGER_FAILURE_TRANSPORT_DSN)%'
      sync: 'sync://'
    routing:
      'App\\Application\\Command\\Event\\CreateEventCommand': async
      'App\\Application\\Command\\Event\\PublishEventCommand': async
      'App\\Application\\Query\\Event\\GetEventDetailsQuery': sync
```

#### Kompletny przykład end-to-end – CreateEvent (2025-10-23T17:38:12.513Z)

Pliki (skróty zawartości, gotowe do wklejenia):

1) Application/Command/Event/CreateEventCommand.php
```php
<?php
namespace App\Application\Command\Event;

final class CreateEventCommand
{
    public function __construct(
        public string $name,
        public \DateTimeImmutable $startsAt
    ) {}
}
```

2) Application/Command/Event/CreateEventHandler.php
```php
<?php
namespace App\Application\Command\Event;

use App\Domain\Event\Entity\Event;
use App\Presentation\Api\Presenter\Event\EventPresenter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[AsMessageHandler]
final class CreateEventHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private TagAwareCacheInterface $cache,
        private HubInterface $hub,
        private EventPresenter $presenter,
    ) {}

    public function __invoke(CreateEventCommand $c): string
    {
        return $this->em->wrapInTransaction(function () use ($c) {
            $event = new Event($c->name, $c->startsAt);
            $this->em->persist($event);
            $this->em->flush();

            $this->cache->invalidateTags(['events']);

            $payload = json_encode($this->presenter->presentListItem($event), JSON_THROW_ON_ERROR);
            $this->hub->publish(new Update('/events', $payload, true));

            return (string) $event->getId();
        });
    }
}
```

3) Presentation/Api/Request/Event/CreateEventRequest.php
```php
<?php
namespace App\Presentation\Api\Request\Event;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateEventRequest
{
    #[Assert\NotBlank]
    public string $name;

    #[Assert\NotBlank]
    public string $startsAt; // ISO8601
}
```

4) Presentation/Api/Response/Event/EventDetailsResponse.php
```php
<?php
namespace App\Presentation\Api\Response\Event;

final class EventDetailsResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $startsAt
    ) {}
}
```

5) Presentation/Api/Presenter/Event/EventPresenter.php
```php
<?php
namespace App\Presentation\Api\Presenter\Event;

use App\Domain\Event\Entity\Event;
use App\Presentation\Api\Response\Event\EventDetailsResponse;

final class EventPresenter
{
    public function presentDetails(Event $e): EventDetailsResponse
    {
        return new EventDetailsResponse(
            (string) $e->getId(),
            $e->getName(),
            $e->getStartsAt()->format(DATE_ATOM)
        );
    }

    public function presentListItem(Event $e): array
    {
        return [
            'id' => (string) $e->getId(),
            'name' => $e->getName(),
            'startsAt' => $e->getStartsAt()->format(DATE_ATOM),
        ];
    }
}
```

6) Presentation/Api/Controller/EventController.php (sync w test, async w prod)
```php
<?php
namespace App\Presentation\Api\Controller;

use App\Application\Command\Event\CreateEventCommand;
use App\Presentation\Api\Presenter\Event\EventPresenter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

final class EventController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private EventPresenter $presenter
    ) {}

    #[Route('/api/events', name: 'api_events_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $cmd = new CreateEventCommand($data['name'], new \DateTimeImmutable($data['startsAt']));
        $this->commandBus->dispatch($cmd);
        return new JsonResponse(['status' => 'accepted'], 202);
    }
}
```

7) config/routes.yaml (fragment)
```yaml
api_events_create:
  path: /api/events
  controller: App\Presentation\Api\Controller\EventController::create
  methods: [POST]
```

8) config/packages/test/messenger.yaml (sync w testach)
```yaml
framework:
  messenger:
    transports:
      async: 'sync://'
```

9) Tests – przykłady
- tests/Unit/Presentation/EventPresenterTest.php
```php
<?php
namespace App\Tests\Unit\Presentation;

use App\Domain\Event\Entity\Event;
use App\Presentation\Api\Presenter\Event\EventPresenter;
use PHPUnit\Framework\TestCase;

final class EventPresenterTest extends TestCase
{
    public function testPresentDetails(): void
    {
        $event = new Event('Name', new \DateTimeImmutable('2025-10-23T12:00:00Z'));
        $p = new EventPresenter();
        $dto = $p->presentDetails($event);
        self::assertSame('Name', $dto->name);
        self::assertSame('2025-10-23T12:00:00+00:00', $dto->startsAt);
    }
}
```

- tests/Functional/Api/CreateEventTest.php
```php
<?php
namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CreateEventTest extends WebTestCase
{
    public function testCreateEventAccepted(): void
    {
        $client = static::createClient();
        $payload = [
            'name' => 'Conf',
            'startsAt' => '2025-10-23T12:00:00Z'
        ];
        $client->request('POST', '/api/events', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload));
        self::assertResponseStatusCodeSame(202);
        self::assertJson($client->getResponse()->getContent());
    }
}
```

### Definition of Done – Faza 3 (CreateEvent)
- Command/Handler działają: persist + transakcja + invalidacja tagów
- Messenger routing: CreateEventCommand → async (prod), sync (test)
- Mercure publikuje Update na "/events" z payloadem DTO
- Brak serializacji Entity w odpowiedzi; Controller zwraca 202
- Testy unit (Presenter) i functional (POST /api/events) zielone

### KPI
- ✅ Min. 3 use‑case’y działają end‑to‑end bez użycia Service/*
- ✅ Tagowa invalidacja działa na listach i szczegółach

---

## Faza 4: Interfejs/API Platform

### Zakres
- Kontrolery/StateProcessor przyjmują Request DTO, wywołują Handler, zwracają Response DTO poprzez Presenter.
- Zabroń bezpośredniej serializacji encji (normalizers tylko dla DTO).

### Deliverables
- [ ] 2 kontrolery przerobione (Users, Events:list)
- [ ] ApiPlatform Provider/Processor dla Events z DTO (Provider dla GET collection, Processor dla POST)
- [ ] Walidacje w Request DTO (Symfony Validator; groups, messages, custom constraints)
- [ ] Paginacja kolekcji (page, itemsPerPage) + default 10, max 100
- [ ] Error handling: ValidationFailedException→422 ErrorDTO; DomainException→400 ErrorDTO

### KPI
- ✅ Api Platform: GET /api/events przez GetCollection+Provider z paginacją (?page, ?itemsPerPage); domyślnie 10
- ✅ Api Platform: POST /api/events przez Processor; walidacje DTO działają; 422 dla błędów z listą violations
- ✅ Brak bezpośredniej serializacji encji (snapshot kontraktów JSON/Schema zielony)
- ✅ Testy functional: list (paginacja + format) i create (walidacja negatywna/pozytywna) zielone

#### Przykład endpointu DTO/Presenter – Events:list (2025-10-23T17:44:35.109Z)
Pliki do dodania:
- Presentation/Api/Response/Event/EventListItemResponse.php (ApiResource dla kolekcji z GetCollection+Provider)
- Presentation/Api/Presenter/Event/EventListPresenter.php
- Presentation/Api/Controller/EventQueryController.php (alternatywa poza ApiPlatform)
- Infrastructure/ApiPlatform/State/Event/EventListProvider.php (kod poniżej)
- Infrastructure/ApiPlatform/State/Event/CreateEventProcessor.php (kod poniżej)
- Presentation/Api/Request/Event/CreateEventRequest.php (constraints + groups; kod poniżej)
- Presentation/Api/Response/Error/ErrorDTO.php (kod poniżej)
- Infrastructure/Http/ExceptionSubscriber.php (mapowanie wyjątków na ErrorDTO; kod poniżej)

1) Response DTO – EventListItemResponse.php
```php
<?php
namespace App\Presentation\Api\Response\Event;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Infrastructure\ApiPlatform\State\Event\EventListProvider;

#[ApiResource(operations: [
    new GetCollection(
        provider: EventListProvider::class,
        paginationEnabled: true,
        paginationItemsPerPage: 10
    )
])]
final class EventListItemResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $startsAt
    ) {}
}
```

2) Provider – Infrastructure/ApiPlatform/State/Event/EventListProvider.php
```php
<?php
namespace App\Infrastructure\ApiPlatform\State\Event;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Domain\Event\Entity\Event as EventEntity;
use App\Presentation\Api\Presenter\Event\EventListPresenter;
use Doctrine\ORM\EntityManagerInterface;

final class EventListProvider implements ProviderInterface
{
    public function __construct(private EntityManagerInterface $em, private EventListPresenter $presenter) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $page = max(1, (int)($context['filters']['page'] ?? 1));
        $perPage = min(100, (int)($context['filters']['itemsPerPage'] ?? 10));
        $offset = ($page - 1) * $perPage;

        $repo = $this->em->getRepository(EventEntity::class);
        $events = $repo->findBy([], ['startsAt' => 'DESC'], $perPage, $offset);

        return $this->presenter->present($events);
    }
}
```

3) Presenter – EventListPresenter.php
```php
<?php
namespace App\Presentation\Api\Presenter\Event;

use App\Domain\Event\Entity\Event;
use App\Presentation\Api\Response\Event\EventListItemResponse;

final class EventListPresenter
{
    /** @param iterable<Event> $events */
    public function present(iterable $events): array
    {
        $out = [];
        foreach ($events as $e) {
            $out[] = new EventListItemResponse(
                (string) $e->getId(),
                $e->getName(),
                $e->getStartsAt()->format(DATE_ATOM)
            );
        }
        return $out;
    }
}
```

4) Processor – Infrastructure/ApiPlatform/State/Event/CreateEventProcessor.php
```php
<?php
namespace App\Infrastructure\ApiPlatform\State\Event;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Command\Event\CreateEventCommand;
use App\Presentation\Api\Request\Event\CreateEventRequest;
use App\Presentation\Api\Presenter\Event\EventPresenter;
use App\Presentation\Api\Response\Event\EventDetailsResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class CreateEventProcessor implements ProcessorInterface
{
    public function __construct(private MessageBusInterface $bus, private ValidatorInterface $validator, private EventPresenter $presenter) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): EventDetailsResponse
    {
        \assert($data instanceof CreateEventRequest);
        $violations = $this->validator->validate($data);
        if (count($violations) > 0) {
            throw new ValidationFailedException($data, $violations);
        }
        $cmd = new CreateEventCommand($data->name, new \DateTimeImmutable($data->startsAt));
        $this->bus->dispatch($cmd);
        return new EventDetailsResponse('pending', $data->name, (new \DateTimeImmutable($data->startsAt))->format(DATE_ATOM));
    }
}
```

5) Request DTO – Presentation/Api/Request/Event/CreateEventRequest.php
```php
<?php
namespace App\Presentation\Api\Request\Event;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

final class CreateEventRequest
{
    #[Assert\NotBlank(groups: ['create'])]
    #[Groups(['create'])]
    public string $name;

    #[Assert\NotBlank(groups: ['create'])]
    #[Groups(['create'])]
    public string $startsAt; // ISO8601
}
```

6) Error DTO – Presentation/Api/Response/Error/ErrorDTO.php
```php
<?php
namespace App\Presentation\Api\Response\Error;

final class ErrorDTO
{
    public function __construct(
        public string $code,
        public string $message,
        public ?array $errors = null
    ) {}
}
```

7) ExceptionSubscriber – Infrastructure/Http/ExceptionSubscriber.php
```php
<?php
namespace App\Infrastructure\Http;

use App\Presentation\Api\Response\Error\ErrorDTO;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onException'];
    }

    public function onException(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();
        if ($e instanceof ValidationFailedException) {
            $violations = [];
            foreach ($e->getViolations() as $v) {
                $violations[] = ['field' => (string) $v->getPropertyPath(), 'message' => (string) $v->getMessage()];
            }
            $dto = new ErrorDTO('validation_failed', 'Validation failed', $violations);
            $event->setResponse(new JsonResponse($dto, 422));
            return;
        }
    }
}
```

8) Controller – EventQueryController.php
```php
<?php
namespace App\Presentation\Api\Controller;

use App\Presentation\Api\Presenter\Event\EventListPresenter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class EventQueryController
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventListPresenter $presenter
    ) {}

    #[Route('/api/events', name: 'api_events_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $events = $this->em->getRepository(\App\Domain\Event\Entity\Event::class)->findBy([], ['startsAt' => 'DESC']);
        return new JsonResponse($this->presenter->present($events));
    }
}
```

### Definition of Done – Faza 4 (Events:list)
- Controller zwraca Response DTO listy, bez serializacji encji
- Presenter mapuje Entity→DTO (ISO8601, jawne pola)
- Endpoint GET /api/events zwraca 200 i tablicę DTO (snapshot kontraktu)
- Test functional dla GET /api/events zielony

---

## Faza 5: Migracja modułami (inkrementalnie)

### Kolejność i kroki
1) Users
- [ ] Usuń hasło z grup odczytu; tylko write‑group lub Request DTO
- [ ] UserPresenter + UserPublicDTO
- [ ] Autoryzacja/roles przez Handlery, nie w encji

2) Events
- [ ] EventPresenter + EventListItemResponse/EventDetailsResponse
- [ ] Commands/Queries + invalidacja tagów
- [ ] RT: publikacja zmian przez Mercure (HubInterface)

3) Tickets/Orders
- [ ] DTO/Presentery, Handlery, płatności przez Infrastructure (Stripe gateway)
- [ ] Tagowanie: 'orders', 'order:{id}', 'event:{id}'

4) Notifications/RT
- [ ] Standaryzacja payloadów RT na Response DTO
- [ ] Tematy Mercure i autoryzacja

### KPI
- ✅ Po każdym module: testy zielone, brak zwracania encji, invalidacja cache działa

---

## Faza 6: Bezpieczeństwo i zgodność

### Zakres
- JWT TTL = 3600, przegląd CustomAuthenticator (użycie/zasady), brak haseł i wrażliwych pól w odpowiedziach.
- Sekrety w Symfony Secrets (APP_SECRET, JWT_PASSPHRASE, STRIPE_SECRET_KEY, MERCURE_JWT_SECRET).

### Deliverables
- [ ] Konfiguracja lexik_jwt_authentication.yaml (RS256, token_ttl=3600, ścieżki kluczy, passphrase z secrets)
- [ ] Symfony Secrets: APP_SECRET, JWT_PASSPHRASE, STRIPE_SECRET_KEY, MERCURE_JWT_SECRET ustawione; usunięte z .env/README
- [ ] CustomAuthenticator: weryfikacja użycia; usunięty jeśli zbędny lub poprawiona walidacja JWT
- [ ] Przegląd grup serializacji i sanitizer pola email/tokenów

#### Przykładowa konfiguracja – lexik_jwt_authentication.yaml (2025-10-23T20:16:52.588Z)
```yaml
lexik_jwt_authentication:
  secret_key: '%kernel.project_dir%/config/jwt/private.pem'
  public_key: '%kernel.project_dir%/config/jwt/public.pem'
  pass_phrase: '%env(resolve:JWT_PASSPHRASE)%'
  token_ttl: 3600
  encoder:
    signature_algorithm: RS256
```

#### Generowanie kluczy i ustawienie secrets
- php bin/console lexik:jwt:generate-keypair --overwrite
- symfony console secrets:set APP_SECRET
- symfony console secrets:set JWT_PASSPHRASE
- symfony console secrets:set STRIPE_SECRET_KEY
- symfony console secrets:set MERCURE_JWT_SECRET
- Usuń wartości z .env/.env.* i README.

#### Definition of Done – Faza 6
- JWT TTL=3600 działa (exp ~ now+3600)
- Sekrety obecne w Symfony Secrets; brak w .env/README
- CustomAuthenticator: usunięty lub działa poprawnie z Lexik JWT
- Brak password w żadnym response

### KPI
- ✅ Brak pola password w jakimkolwiek response (test kontraktowy)

---

## Faza 7: Jakość i CI

### Zakres
- Deptrac – reguły warstw; PHPStan max; Rector recepty; PHPCS; testy jednostkowe Handlerów/Presenterów; kontrakty JSON.
- Blokady w CI: brak nowych klas w src/Service; zakaz serializacji encji; coverage kluczowych use‑case’ów.

### Deliverables
- [ ] Pipeline z krokami: php-cs, phpstan, deptrac, phpunit, api-contracts, openapi-validate
- [ ] Reguły Deptrac: Interface→App→Domain; Infrastructure→App/Domain; brak odwrotnych
- [ ] GitHub Actions: matrix PHP 8.2/8.3; cache deps; artifacty raportów (phpstan, deptrac)
- [ ] Snapshoty kontraktów JSON i walidacja OpenAPI w CI

### KPI
- ✅ CI czerwieni naruszenia; zielone po naprawie; kontrakty API zgodne z OpenAPI
- ✅ Brak nowych naruszeń warstwowych (Deptrac) i brak serializacji encji (grep w CI)

---

## Faza 8: Dokumentacja i operacyjne

### Zakres
- ADR per decyzja (architektura, cache, RT, messaging, bezpieczeństwo)
- README per warstwa (Domain, Application, Infrastructure, Presentation) i cookbook „jak dodać endpoint” E2E
- Standardy: Contributing, versioning/release notes (CHANGELOG), konwencje nazw, struktura repo, zasady PR/code review
- Dokumentacja API (OpenAPI) + kontrakty JSON/snapshoty i polityka utrzymania
- Runbooki operacyjne: secrets, rotacje kluczy, deploy, rollback, monitoring (2025-10-23T20:19:27.785Z)

### Deliverables
- [ ] ADR: DTO/Presenter, TagAwareCache, Mercure RT, Messaging
- [ ] README: Domain, Application, Infrastructure, Presentation

#### Szablon ADR (2025-10-23T20:20:39.097Z)
```
# ADR: <Tytuł decyzji>

- Status: proposed | accepted | superseded by ADR-XXX
- Data: 2025-10-23
- Kontekst
  Opisz problem, wymagania niefunkcjonalne, ograniczenia (np. wydajność, bezpieczeństwo), rozważane opcje.
- Decyzja
  Jaka decyzja architektoniczna została podjęta i dlaczego.
- Konsekwencje
  Plusy, minusy, wpływ na koszty/utrzymanie, ryzyka i mitigacje.
- Alternatywy rozważone
  A, B, C z krótkim uzasadnieniem odrzuceń.
- Linki
  Ticket, PR, dokumenty referencyjne.
```

#### Cookbook: dodanie endpointu E2E (DTO → Handler → Presenter → API) – 2025-10-23T20:20:39.097Z
- Krok 1: Zdefiniuj Request DTO (Presentation/Api/Request/<Ctx>/<Action>Request.php) z walidacją.
- Krok 2: Zdefiniuj Response DTO (Presentation/Api/Response/<Ctx>/<Name>Response.php).
- Krok 3: Presenter (Presentation/Api/Presenter/<Ctx>/<Name>Presenter.php) – mapowanie Entity→DTO, formaty ISO8601/Money.
- Krok 4: Use‑case: Command/Query + Handler (src/Application/{Command|Query}/<Ctx>/...). Transakcje w Handlerze mutującym.
- Krok 5: Cache (TagAware) w Handlerze/Query; polityka tagów i invalidacja po mutacjach.
- Krok 6: RT (opcjonalnie): HubInterface publish z payloadem Response DTO i tematami.
- Krok 7: API Platform: ApiResource z Processor/Provider albo Controller routing.
- Krok 8: Testy: unit Presenter/Handler, functional API (happy + validation error), snapshot kontraktu JSON.
- Krok 9: Dokumentacja: aktualizacja OpenAPI i cookbooka, ADR jeśli decyzje architektoniczne.


### KPI
- ✅ Nowy dev wdraża endpoint wg cookbooka bez dotykania encji

---

## Plan migracji – checklisty per moduł

### Users
- [ ] Domain/User/Entity/User.php: usuń password z grup odczytu (tylko write)
- [ ] Presenter/DTO: Presentation/Api/Presenter/User/UserPresenter.php, Presentation/Api/Response/User/UserPublicDTO.php (id, email, createdAt ISO8601)
- [ ] Auth przez DTO: Presentation/Api/Request/Auth/RegisterRequest.php, LoginRequest.php; kontrolery/ApiPlatform Processory zwracają Response DTO
- [ ] Testy: tests/Unit/Presentation/UserPresenterTest.php; tests/Functional/Api/Auth/RegisterTest.php, LoginTest.php (kontrakty JSON)

### Events
- [ ] Presenters/DTO: Presentation/Api/Presenter/Event/EventPresenter.php; Response/Event/EventListItemResponse.php, EventDetailsResponse.php
- [ ] Application: Command/Event/{Create,Update,Publish}EventCommand(+Handler); Query/Event/{GetEventDetails,GetEventList}Query(+Handler)
- [ ] Cache: TagAware – tagi 'events', 'event:{id}', invalidacja w Handlerach mutujących
- [ ] RT: HubInterface publish na '/events' i '/events/{id}' z Response DTO; testy RT payloadów

### Tickets/Orders
- [ ] Presenters/DTO: OrderSummaryResponse, TicketTypeDTO; Adapter Stripe w Infrastructure z config przez Symfony Secrets
- [ ] Application: Command/Order/{CreateOrder,PayOrder} (+Handler); Query/Order/{GetOrderDetails,GetUserOrders}
- [ ] Cache: tagi 'orders', 'order:{id}', 'event:{id}'; invalidacja po płatnościach i anulacjach
- [ ] Testy: unit Handlery (idempotencja płatności), functional API kontrakty

---

## Ryzyka i mitigacje
- Niewidoczne regresje kontraktów API → testy kontraktowe/snapshoty.
- Zależności kołowe podczas migracji → Deptrac jako gate, migracja modułami.
- Spadek wydajności przez warstwy → profiling i cache na Application.

---

## Harmonogram (wysoki poziom)
- Tydzień 1: Faza 1–2 (audyt + kontrakty DTO/Presenter dla Users/Events)
- Tydzień 2: Faza 3–4 (use‑case’y, 2–3 endpointy Users/Events)
- Tydzień 3: Faza 5 (Events full, start Tickets)
- Tydzień 4: Faza 5 (Orders/Payments), Faza 6–7 (security + CI), Faza 8 (doki)

Milestone’y: M1 (Users), M2 (Events), M3 (Tickets/Orders), M4 (Stabilizacja/CI/doc)

---

## Definition of Done (global)
- 0 metod prezentacyjnych w Entities; 100% endpointów zwraca Response DTO
- Deptrac/CI zielone; brak nowych klas w src/Service
- Cache tagi działają (testy invalidacji)
- Sekrety w Symfony Secrets; security testy przechodzą

