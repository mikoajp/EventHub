# 📋 Plan Refaktoringu EventHub

## Cele

1. **Eliminacja duplikacji** - Usunięcie zduplikowanego kodu między legacy a DDD
2. **Dokończenie migracji do DDD** - Konsekwentne użycie Application/Domain Services
3. **Standaryzacja** - Jednolite podejście do cache, messaging, validation
4. **Cleanup** - Usunięcie nieużywanych komponentów
5. **Separation of Concerns** - Przeniesienie logiki biznesowej z encji

---

## Faza 1: Krytyczne Naprawy (Priorytet: WYSOKI) 🔴

### 1.1 Konflikt nazw EventStatisticsService
**Problem:** Dwa pliki o tej samej nazwie klasy!

**Działania:**
```bash
# 1. Przemianować legacy service
mv src/Service/EventStatisticsService.php src/Service/EventStatisticsCacheService.php

# 2. Update namespace i nazwa klasy w pliku
class EventStatisticsCacheService { ... }

# 3. Update w UpdateEventStatisticsHandler
use App\Service\EventStatisticsCacheService;
```

**Czas:** 30 minut  
**Ryzyko:** Niskie  
**Impact:** Krytyczny (fix autoloader conflict)

---

### 1.2 Usunięcie nieużywanego EventService
**Problem:** Kompletnie zastąpiony przez EventApplicationService

**Działania:**
```bash
# 1. Sprawdź czy na pewno nieużywany (grep)
grep -r "EventService" backend/src --exclude-dir=vendor

# 2. Usuń plik
rm backend/src/Service/EventService.php

# 3. Usuń dependency injection jeśli jest w services.yaml
```

**Czas:** 15 minut  
**Ryzyko:** Niskie (już nie używany)  
**Impact:** Średni (cleanup)

---

### 1.3 Konsolidacja EmailService
**Problem:** Duplikacja między EmailService i SymfonyMailerAdapter

**Działania:**
```php
// 1. W NotificationService zamień EmailService na EmailServiceInterface
public function __construct(
    private EmailServiceInterface $emailService, // zamiast EmailService
    // ...
) {}

// 2. Usuń legacy EmailService
rm backend/src/Service/EmailService.php

// 3. Sprawdź services.yaml - użyj EmailServiceInterface → SymfonyMailerAdapter
```

**Czas:** 30 minut  
**Ryzyko:** Średnie (używany w NotificationService)  
**Impact:** Wysoki (lepszy design pattern)

---

## Faza 2: Migracja Kontrolerów (Priorytet: WYSOKI) 🔴

### 2.1 TicketController - Migracja do TicketApplicationService

**Obecny stan:**
```php
class TicketController {
    public function __construct(
        private TicketService $ticketService // Legacy
    ) {}
}
```

**Docelowy:**
```php
class TicketController {
    public function __construct(
        private TicketApplicationService $ticketApplicationService
    ) {}
    
    #[Route('/my', methods: ['GET'])]
    public function getMyTickets(#[CurrentUser] User $user): JsonResponse {
        $tickets = $this->ticketApplicationService->getUserTickets($user);
        return $this->json($tickets);
    }
    
    #[Route('/purchase', methods: ['POST'])]
    public function purchase(Request $request, #[CurrentUser] User $user): JsonResponse {
        // Użyj PurchaseTicketCommand zamiast direct service call
    }
}
```

**Nowe endpointy do dodania:**
- `GET /api/tickets/my` - lista biletów użytkownika
- `POST /api/tickets/purchase` - zakup biletu
- `POST /api/tickets/{id}/cancel` - anulowanie biletu

**Czas:** 2 godziny  
**Ryzyko:** Średnie  
**Impact:** Wysoki

---

### 2.2 PaymentHandler - Migracja do PaymentApplicationService

**Obecny stan:**
```php
class ProcessPaymentHandler {
    public function __construct(
        private PaymentService $paymentService // Legacy mock
    ) {}
}
```

**Docelowy:**
```php
class ProcessPaymentHandler {
    public function __construct(
        private PaymentApplicationService $paymentApplicationService
    ) {}
    
    public function __invoke(ProcessPaymentCommand $command): PaymentResult {
        return $this->paymentApplicationService->processPayment(
            $command->paymentMethodId,
            $command->amount,
            $command->currency ?? 'USD',
            $command->metadata ?? []
        );
    }
}
```

**Korzyści:**
- ✅ Walidacja walut i kwot
- ✅ Automatyczna kalkulacja opłat
- ✅ Integracja z PaymentGatewayInterface
- ✅ Lepsze logowanie

**Czas:** 1 godzina  
**Ryzyko:** Średnie  
**Impact:** Wysoki (lepsze payment processing)

---

### 2.3 AuthController - Konsolidacja

**Problem:** Używa zarówno UserApplicationService jak i ma legacy AuthService

**Działania:**
```php
class AuthController {
    public function __construct(
        private UserApplicationService $userApplicationService,
        // Usunąć wszystkie odniesienia do AuthService
    ) {}
    
    #[Route('/register', methods: ['POST'])]
    public function register(Request $request): JsonResponse {
        $data = $this->requestValidator->extractJsonData($request);
        
        $registrationDTO = new UserRegistrationDTO(
            $data['email'] ?? '',
            $data['password'] ?? '',
            $data['firstName'] ?? '',
            $data['lastName'] ?? '',
            $data['phone'] ?? null
        );
        
        $user = $this->userApplicationService->registerUser($registrationDTO);
        return $this->json(
            $this->userApplicationService->formatRegistrationResponse($user),
            Response::HTTP_CREATED
        );
    }
}
```

**Usuń:**
- `src/Service/AuthService.php`

**Czas:** 1 godzina  
**Ryzyko:** Niskie (łatwe do przetestowania)  
**Impact:** Średni

---

## Faza 3: Konsolidacja Notification Layer (Priorytet: ŚREDNI) 🟡

### 3.1 Ujednolicenie NotificationService

**Problem:** Dwa serwisy notification + mieszanie Mercure i MessageBus

**Rekomendowane podejście:**

```
┌─────────────────────────────────────────┐
│  NotificationApplicationService         │
│  (Orchestration layer)                  │
└──────────┬─────────────┬────────────────┘
           │             │
    ┌──────▼──────┐  ┌──▼───────────────┐
    │ EmailService│  │ MessageBus       │
    │ Interface   │  │ (RabbitMQ)       │
    └─────────────┘  └──┬───────────────┘
                        │
              ┌─────────▼──────────┐
              │ Event Handlers     │
              │ → MercurePublisher │
              │   (Real-time UI)   │
              └────────────────────┘
```

**Działania:**
1. Zachować NotificationApplicationService
2. Usunąć legacy NotificationService
3. Mercure używać TYLKO dla real-time UI updates przez dedykowane handlery
4. RabbitMQ dla asynchronicznych tasks (email, etc.)

**Czas:** 3 godziny  
**Ryzyko:** Średnie-Wysokie  
**Impact:** Wysoki (clean architecture)

---

## Faza 4: Refaktor Encji - Thin Models (Priorytet: ŚREDNI) 🟡

### 4.1 Event Entity - Przeniesienie logiki biznesowej

**Problem:** Fat Model - logika biznesowa w encji

**Logika do przeniesienia do EventDomainService:**
```php
// Z Event.php przenieś do EventDomainService:
- canBeModified()
- canBeCancelled()
- canBePublished()
- canBeUnpublished()
- canBeCompleted()
- markAsCompleted()
- hasAvailableTicketType()
```

**Logika do przeniesienia do EventPresenter/DTO:**
```php
// Formatowanie - przenieś do EventPresenter:
- getStatusLabel()
- getEventDateFormatted()
- getCreatedAtFormatted()
- getDaysUntilEvent()
```

**Pozostawić w encji:**
```php
// Tylko gettery/settery i computed properties:
- getTicketsSold() - jeśli to property
- getAvailableTickets() - computed
- getTotalRevenue() - computed
- getOccupancyRate() - computed
```

**Czas:** 4 godziny  
**Ryzyko:** Średnie  
**Impact:** Wysoki (better design)

---

### 4.2 User, Ticket, Order - Similar refactoring

Zastosować podobne podejście dla innych encji.

**Czas:** 2 godziny każda  
**Ryzyko:** Średnie  
**Impact:** Średni

---

## Faza 5: Standaryzacja Cache (Priorytet: NISKI) 🟢

### 5.1 Jeden CacheInterface dla wszystkich

**Problem:** Mieszanie CacheService i CacheInterface

**Docelowo:**
- Wszędzie używać `CacheInterface` 
- `RedisCacheAdapter` implementuje `CacheInterface`
- Usunąć legacy `CacheService` jeśli możliwe

**Ujednolicić strategię tagowania:**
```php
$this->cache->set($key, $value, $ttl, ['events', 'event:'.$eventId]);
$this->cache->invalidateTags(['events']); // Invalidate wszystkie wydarzenia
```

**Czas:** 3 godziny  
**Ryzyko:** Średnie  
**Impact:** Średni

---

## Faza 6: Dokończenie CQRS (Priorytet: NISKI) 🟢

### 6.1 Implementacja brakujących Commands

**Do zrobienia:**
```php
// 1. CancelEventCommand + Handler
class CancelEventCommand {
    public function __construct(
        public readonly string $eventId,
        public readonly string $userId,
        public readonly string $reason
    ) {}
}

// 2. ReserveTicketCommand + Handler
class ReserveTicketCommand {
    public function __construct(
        public readonly string $ticketTypeId,
        public readonly string $userId,
        public readonly int $quantity
    ) {}
}
```

**Czas:** 2 godziny każdy  
**Ryzyko:** Niskie  
**Impact:** Średni

---

## Faza 7: Nowe Endpointy API (Priorytet: NISKI) 🟢

### 7.1 Rozbudowa TicketController

```php
GET    /api/tickets/my              - Lista moich biletów
POST   /api/tickets/purchase        - Zakup biletu
POST   /api/tickets/{id}/cancel     - Anuluj bilet
POST   /api/tickets/{id}/transfer   - Transfer biletu
```

### 7.2 Zaawansowane statystyki

```php
GET    /api/events/{id}/analytics   - Zaawansowane analytics
                                      (użyj Domain EventStatisticsService)
```

### 7.3 Payment endpoints

```php
GET    /api/payments/{id}/status    - Status płatności
POST   /api/payments/{id}/refund    - Zwrot płatności
POST   /api/payments/fees/calculate - Kalkulacja opłat
```

**Czas:** 1 dzień  
**Ryzyko:** Niskie  
**Impact:** Wysoki (nowa funkcjonalność)

---

## Harmonogram Sugerowany

### Sprint 1 (Tydzień 1) - Krytyczne Naprawy
- ✅ Faza 1: Krytyczne naprawy (2 godziny)
- ✅ Faza 2.1: TicketController migration (2 godziny)
- ✅ Faza 2.2: PaymentHandler migration (1 godzina)
- ✅ Faza 2.3: AuthController cleanup (1 godzina)
- **Łącznie: 6 godzin = 1 dzień roboczy**

### Sprint 2 (Tydzień 2) - Notification Layer
- ✅ Faza 3: Notification consolidation (3 godziny)
- ✅ Testing i bugfixing (2 godziny)
- **Łącznie: 5 godzin**

### Sprint 3 (Tydzień 3) - Clean Architecture
- ✅ Faza 4.1: Event entity refactor (4 godziny)
- ✅ Faza 4.2: Other entities (4 godziny)
- **Łącznie: 8 godzin = 1 dzień roboczy**

### Sprint 4 (Tydzień 4) - Standaryzacja i Nowe Funkcje
- ✅ Faza 5: Cache standardization (3 godziny)
- ✅ Faza 6: Missing CQRS commands (4 godziny)
- ✅ Faza 7: New endpoints (8 godzin)
- **Łącznie: 15 godzin = 2 dni robocze**

### Łączny czas: ~34 godziny = 4-5 dni roboczych

---

## Pliki do Usunięcia

Po zakończeniu migracji można bezpiecznie usunąć:

```bash
# Legacy Services
backend/src/Service/EventService.php
backend/src/Service/AuthService.php
backend/src/Service/EmailService.php
backend/src/Service/NotificationService.php
backend/src/Service/TicketService.php
backend/src/Service/PaymentService.php

# Po przemianowaniu
backend/src/Service/EventStatisticsService.php → EventStatisticsCacheService.php
```

**Zachować:**
```bash
backend/src/Service/CacheService.php (lub zmigrować do Infrastructure)
backend/src/Service/ErrorHandlerService.php
backend/src/Service/ValidationService.php (lub zmigrować do Infrastructure)
backend/src/Service/TicketAvailabilityService.php (lub przenieść do Domain)
backend/src/Service/CacheStatsService.php (lub przenieść do Infrastructure/Monitoring)
```

---

## Metryki Sukcesu

### Przed refactoringiem:
- **Legacy Services:** 12 plików
- **Application Services:** 5 plików
- **Duplikacja:** ~40% kodu
- **Nieużywane:** ~30% Application Services
- **Kontrolery używają:** Mix legacy + DDD

### Po refactoringu:
- **Legacy Services:** 3-4 pliki (tylko utility)
- **Application Services:** 5 plików (100% używane)
- **Duplikacja:** <5%
- **Nieużywane:** 0%
- **Kontrolery używają:** Tylko DDD (Application Services)

---

## Ryzyko i Mitigacja

### Wysokie Ryzyko:
1. **Notification layer refactor** - dużo zależności
   - Mitigacja: Dobre testy integracyjne, stopniowa migracja

2. **Entity refactoring** - może złamać istniejące funkcje
   - Mitigacja: Testy jednostkowe, migracja po jednej encji

### Średnie Ryzyko:
1. **Cache refactoring** - może wpłynąć na performance
   - Mitigacja: Monitoring, rollback plan

2. **Controller migration** - zmiana API contracts
   - Mitigacja: Backward compatibility, versioning

### Niskie Ryzyko:
1. **Usunięcie nieużywanych plików** - łatwe do cofnięcia
   - Mitigacja: Git, sprawdzenie przed usunięciem

---

## Rekomendacje Dodatkowe

### 1. Testy
Przed rozpoczęciem refactoringu:
- ✅ Napisać testy integracyjne dla kluczowych ścieżek
- ✅ Testy dla: auth, event creation, ticket purchase, payment
- ✅ Minimum 70% code coverage dla Application/Domain

### 2. Dokumentacja
- ✅ Zaktualizować README.md z nową architekturą
- ✅ Dodać ADR (Architecture Decision Records)
- ✅ API documentation (OpenAPI/Swagger)

### 3. Monitoring
- ✅ Dodać metrics dla performance
- ✅ Error tracking (Sentry?)
- ✅ Cache hit/miss rates

### 4. CI/CD
- ✅ Automated tests w pipeline
- ✅ Code quality checks (PHPStan, Psalm)
- ✅ Deployment automation

---
