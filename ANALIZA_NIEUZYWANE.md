# 🗑️ Nieużywane i Częściowo Używane Funkcjonalności

## Podsumowanie

Projekt zawiera wiele **gotowych ale nieużywanych** komponentów, które zostały przygotowane w ramach migracji do DDD, ale nie zostały podłączone do kontrolerów.

---

## 1. NIEUŻYWANE Application Services

### ❌ PaymentApplicationService
**Lokalizacja:** `src/Application/Service/PaymentApplicationService.php`

**Funkcje:**
- `processPayment()` - z walidacją i fee calculation
- `refundPayment()` - z 30-day refund window check
- `getPaymentStatus()` - pobieranie statusu
- `calculatePaymentFees()` - kalkulacja opłat

**Dlaczego nieużywany:**
- `ProcessPaymentHandler` używa starego `PaymentService`
- Nie podłączony do żadnego kontrolera

**Co można zyskać używając go:**
- ✅ Walidacja walut (USD, EUR, GBP, PLN)
- ✅ Walidacja kwot (max $10,000)
- ✅ Automatyczna kalkulacja opłat (2.5-3.5% + fixed fee)
- ✅ Sprawdzanie czy refund jest możliwy (30 dni)
- ✅ Lepsze separation of concerns
- ✅ Używa PaymentGatewayInterface (łatwa zmiana gateway)

**REKOMENDACJA:** Podmienić w ProcessPaymentHandler

---

### ❌ TicketApplicationService (95% nieużywany)
**Lokalizacja:** `src/Application/Service/TicketApplicationService.php`

**Funkcje nieużywane:**
- `getEventAvailability()` - dostępność dla całego wydarzenia
- `purchaseTicket()` - kompletny flow zakupu
- `confirmTicketPurchase()` - potwierdzenie po płatności
- `getUserTickets()` - lista biletów użytkownika z cache
- `cancelTicket()` - anulowanie biletu

**Funkcje używane:**
- ❌ ŻADNE! Kontroler używa `TicketService.checkTicketAvailability()`

**Dlaczego nieużywany:**
- `TicketController` ma tylko jeden endpoint `/availability`
- Używa starego `TicketService`
- Brakuje endpointów dla zakupu, anulowania, etc.

**Co można zyskać:**
- ✅ Cache dla biletów użytkownika
- ✅ Gotowa logika zakupu biletu
- ✅ Automatyczna walidacja dostępności
- ✅ Invalidation cache po zakupie
- ✅ Integracja z TicketDomainService

**REKOMENDACJA:** 
- Rozbudować TicketController o brakujące endpointy
- Użyć TicketApplicationService zamiast TicketService

---

## 2. NIEUŻYWANE Domain Services

### ❌ EventStatisticsService (Domain Analytics)
**Lokalizacja:** `src/Domain/Analytics/Service/EventStatisticsService.php`

**Zaawansowane funkcje:**
- `calculateBasicStats()` - capacity, tickets sold, occupancy rate
- `calculateRevenueStats()` - total, average, projected revenue
- `calculateSalesTimeline()` - sprzedaż w czasie
- `calculateTicketTypeBreakdown()` - breakdown per ticket type
- `calculatePerformanceMetrics()` - conversion rate, sales velocity, time to sell out
- `estimateTimeToSellOut()` - predykcja wyprzedania
- `findPeakSalesDay()` - dzień z największą sprzedażą
- `calculatePerformanceScore()` - weighted score (0-100)

**Dlaczego nieużywany:**
- Istnieje prosty `EventStatisticsService` w `src/Service/`
- Kontroler używa prostego wrapera cache
- Brak endpointu wykorzystującego te zaawansowane statystyki

**Co można zyskać:**
- ✅ Zaawansowane analityki dla organizatorów
- ✅ Predykcje sprzedaży
- ✅ Revenue forecasting
- ✅ Performance scoring
- ✅ Timeline visualization data

**REKOMENDACJA:**
- Przemianować legacy EventStatisticsService na EventStatisticsCacheService
- Użyć Domain version w endpoint `/events/{id}/statistics`
- Dodać dedykowany analytics endpoint

---

## 3. CZĘŚCIOWO UŻYWANE Serwisy Legacy

### ⚠️ EventService (Legacy)
**Używane funkcje:**
- ❌ BRAK - EventController używa EventApplicationService

**Nieużywane funkcje:**
- `getPublishedEvents()`
- `findEventOrFail()`
- `getEventStatistics()`
- `createEventFromDTO()`
- `updateEventFromDTO()`
- `publishEvent()`
- `unpublishEvent()`
- `cancelEvent()`
- `formatEventsCollectionResponse()`
- ... i więcej

**Status:** Kompletnie zastąpiony przez EventApplicationService

**REKOMENDACJA:** ✅ Bezpieczne usunięcie - nie jest używany

---

### ⚠️ AuthService (Legacy)
**Używane funkcje w AuthController:**
- ❌ BRAK - używa UserApplicationService

**Funkcje w kodzie:**
- `validateUser()` - helper method
- `registerUserFromRequest()` - parsuje Request zamiast DTO
- `formatLoginResponse()` - formatowanie (duplikat)
- `formatUserProfileResponse()` - z cache (duplikat)
- `formatRegistrationResponse()` - formatowanie (duplikat)

**Status:** Częściowo zastąpiony przez UserApplicationService

**REKOMENDACJA:** Migracja do UserApplicationService, usunięcie AuthService

---

### ⚠️ NotificationService (Legacy)
**Używane funkcje:**
- Prawdopodobnie w niektórych handlerach (trudno stwierdzić bez trace'owania)

**Funkcje:**
- `notifyEventPublished()` - używa MercurePublisher
- `shareOnSocialMedia()` - social media integration
- `sendRealTimeUpdate()` - raw Mercure
- `notifyEventUpdated()` - update notifications
- `notifyEventCancelled()` - cancellation flow

**Status:** Konkuruje z NotificationApplicationService

**Problem:** Dwa różne podejścia:
- NotificationService → EmailService + MercurePublisher (direct)
- NotificationApplicationService → EmailServiceInterface + MessageBusInterface

**REKOMENDACJA:** Standaryzacja na NotificationApplicationService + MessageBus

---

## 4. NIEUŻYWANE Infrastructure Components

### ✅ MercureAdapter (Używany ale...)
**Lokalizacja:** `src/Infrastructure/Messaging/MercureAdapter.php`

**Funkcje:**
- `publishEvent()`
- `publishNotification()`
- `publishSocial()`
- `publish()`

**Problem:** 
- Implementuje MessageBusInterface ale używa Mercure
- RabbitMQ jest skonfigurowany w messenger.yaml
- Mieszanie koncepcji: MessageBus (RabbitMQ) vs Real-time (Mercure)

**REKOMENDACJA:** 
- Rozdzielić: RabbitMQ dla command/event bus, Mercure dla real-time UI updates
- Mercure powinien być używany przez dedykowany RealTimeService, nie MessageBus

---

### ❌ MercurePublisher (Duplikat?)
**Lokalizacja:** `src/Infrastructure/RealTime/MercurePublisher.php`

**Funkcje:**
- `publishEvent()`
- `publishNotification()`
- `publishSocial()`
- `publishTicketPurchase()`
- `publish()`

**Problem:** Podobny do MercureAdapter

**Status:** Używany w NotificationService

**REKOMENDACJA:** Konsolidacja - jeden serwis Mercure

---

## 5. NIEUŻYWANE Message Commands/Queries

### ✅ Używane Commands:
- `CreateEventCommand` → CreateEventHandler ✅
- `PublishEventCommand` → PublishEventHandler ✅
- `ProcessPaymentCommand` → ProcessPaymentHandler ✅
- `PurchaseTicketCommand` → PurchaseTicketHandler ✅

### ❌ Nieużywane Commands:
- `CancelEventCommand` - ❌ Brak handlera!
- `ReserveTicketCommand` - ❌ Brak handlera!

### ❌ Nieużywane Queries:
- `CheckTicketAvailabilityQuery` - ❌ Brak handlera!
- `GetEventStatisticsQuery` → GetEventStatisticsHandler ✅ (ale handler używa legacy service)

**REKOMENDACJA:**
- Dokończyć implementację CancelEventCommand + Handler
- Dokończyć ReserveTicketCommand + Handler
- Lub usunąć jeśli nie są planowane

---

## 6. NIEUŻYWANE Entity Features

### Event Entity - Niewykorzystane metody:

**Business Logic Methods (w encji!):**
- `canBeModified()` - logika w encji zamiast w serwisie
- `canBeCancelled()` - jw.
- `canBePublished()` - jw.
- `canBeUnpublished()` - jw.
- `canBeCompleted()` - jw.
- `markAsCompleted()` - zmiana stanu w encji
- `addToWaitingList()` - waiting list logic (niekompletna)
- `hasAvailableTicketType()` - iteracja po kolekcji
- `getTicketTypeByName()` - query logic w encji

**Computed Properties:**
- `getDaysUntilEvent()` - w groups, ale czy używane?
- `getStatusLabel()` - formatowanie w encji
- `getEventDateFormatted()` - formatowanie w encji
- `getOccupancyRate()` - kalkulacja w encji

**Problem:** **Fat Model Anti-pattern**
- Logika biznesowa w encjach
- Formatowanie w encjach
- Query logic w encjach

**REKOMENDACJA:**
- Przenieść logikę biznesową do DomainService
- Formatowanie do Presenters/DTOs
- Pozostawić w encji tylko podstawowe gettery/settery

---

## 7. NIEUŻYWANE Repositories Methods

### EventRepository - Potencjalnie nieużywane:
- `findPublishedEvents()` - używane?
- `findEventsWithFilters()` - używane w EventApplicationService
- `getUniqueVenues()` - dla filtrów
- `getPriceRange()` - dla filtrów

### TicketRepository - Do sprawdzenia:
- `checkAvailability()` - używane
- `getTotalRevenue()` - używane w statystykach
- `getSalesByTicketType()` - używane w statystykach
- `getSalesTimeline()` - używane w statystykach
- `getRevenueByTicketType()` - używane w statystykach

**Status:** Większość używana w serwisach statystyk

---

## 8. NIEUŻYWANE Endpointy API

### Brakujące ale potrzebne endpointy:

**Tickets:**
- `POST /api/tickets/purchase` - zakup biletu (jest handler, brak endpointu)
- `GET /api/tickets/my` - moje bilety (jest w TicketApplicationService)
- `POST /api/tickets/{id}/cancel` - anulowanie (jest w TicketApplicationService)
- `POST /api/tickets/{id}/transfer` - transfer (jest w TicketDomainService)

**Events:**
- `POST /api/events/{id}/unpublish` - cofnięcie publikacji (jest w EventService)
- `GET /api/events/{id}/analytics` - zaawansowane statystyki (jest w Domain)

**Payments:**
- `GET /api/payments/{id}/status` - status płatności
- `POST /api/payments/{id}/refund` - zwrot (jest w PaymentApplicationService)

**Admin:**
- `GET /admin/cache/stats` - statystyki cache ✅ (istnieje)
- `POST /admin/cache/clear` - czyszczenie cache ✅ (istnieje)

**REKOMENDACJA:** Dodać brakujące endpointy lub usunąć nieużywane serwisy

---

