# 🔄 Analiza Duplikacji Kodu w EventHub

## Podsumowanie

Projekt zawiera **znaczącą duplikację funkcjonalności** między legacy kodem (`src/Service/`) a nową architekturą DDD (`src/Application/Service/` + `src/Domain/`).

---

## 1. EventService vs EventApplicationService + EventDomainService

### EventService (Legacy - `src/Service/EventService.php`)
**Funkcje:**
- `getPublishedEvents()` - pobiera opublikowane wydarzenia z cache
- `findEventOrFail()` - znajduje wydarzenie lub rzuca wyjątek
- `getEventStatistics()` - zwraca statystyki wydarzenia
- `createEventFromDTO()` - tworzy wydarzenie z DTO
- `updateEventFromDTO()` - aktualizuje wydarzenie z DTO
- `publishEvent()` - publikuje wydarzenie
- `unpublishEvent()` - cofa publikację
- `cancelEvent()` - anuluje wydarzenie
- `formatEventsCollectionResponse()` - formatuje kolekcję wydarzeń
- `canUserModifyEvent()` - sprawdza uprawnienia użytkownika

**Status:** ⚠️ **CZĘŚCIOWO UŻYWANY**

### EventApplicationService (Nowy)
**Funkcje:**
- `createEvent()` - deleguje do EventDomainService
- `updateEvent()` - deleguje do EventDomainService
- `publishEvent()` - deleguje do EventPublishingService
- `getPublishedEvents()` - cache + repository
- `getEventById()` - cache + repository
- `getEventsWithFilters()` - z filtrami i cache
- `getFilterOptions()` - opcje filtrowania

**Status:** ✅ **AKTYWNIE UŻYWANY** w EventController

### EventDomainService (Nowy)
**Funkcje:**
- `createEvent()` - czysta logika domeny
- `updateEvent()` - czysta logika domeny
- `canUserModifyEvent()` - logika biznesowa
- `isEventPublishable()` - walidacja

**Status:** ✅ **AKTYWNIE UŻYWANY**

### EventPublishingService (Nowy)
**Funkcje:**
- `publishEvent()` - dedykowana logika publikacji
- `canUserPublishEvent()` - sprawdzanie uprawnień
- `cancelEvent()` - logika anulowania

**Status:** ✅ **AKTYWNIE UŻYWANY**

### 🔍 DUPLIKACJE:
1. ❌ `publishEvent()` - istnieje w EventService i EventPublishingService
2. ❌ `canUserModifyEvent()` - EventService i EventDomainService
3. ❌ `getPublishedEvents()` - EventService i EventApplicationService
4. ❌ `cancelEvent()` - EventService i EventPublishingService
5. ❌ Cache logic zduplikowana w obu

**REKOMENDACJA:** Usunąć EventService, przenieść kontrolery na EventApplicationService

---

## 2. AuthService vs UserApplicationService + UserDomainService

### AuthService (Legacy - `src/Service/AuthService.php`)
**Funkcje:**
- `validateUser()` - walidacja użytkownika
- `registerUserFromRequest()` - rejestracja z Request (nie DTO!)
- `formatLoginResponse()` - formatowanie odpowiedzi
- `formatUserProfileResponse()` - profil z cache
- `formatRegistrationResponse()` - odpowiedź rejestracji

**Status:** ✅ **UŻYWANY** w AuthController

### UserApplicationService (Nowy)
**Funkcje:**
- `registerUser()` - rejestracja z DTO (nie Request)
- `updateUserProfile()` - aktualizacja profilu
- `getUserProfile()` - z cache
- `formatLoginResponse()` - formatowanie
- `formatRegistrationResponse()` - formatowanie

**Status:** ✅ **UŻYWANY** w AuthController

### UserDomainService (Nowy)
**Funkcje:**
- `createUser()` - czysta logika tworzenia
- `updateUserProfile()` - czysta logika
- `changePassword()` - zmiana hasła
- `promoteToOrganizer()` - promocja roli
- `demoteFromOrganizer()` - degradacja

**Status:** ✅ **UŻYWANY**

### 🔍 DUPLIKACJE:
1. ❌ `formatLoginResponse()` - AuthService i UserApplicationService
2. ❌ `formatRegistrationResponse()` - w obu
3. ❌ Cache logic dla profilu użytkownika
4. ❌ Logika rejestracji w dwóch miejscach

**REKOMENDACJA:** Konsolidacja - AuthController powinien używać tylko UserApplicationService

---

## 3. PaymentService vs PaymentApplicationService + PaymentDomainService

### PaymentService (Legacy - `src/Service/PaymentService.php`)
**Funkcje:**
- `processPayment()` - mock/stub implementacja
- `refundPayment()` - mock zwrotu

**Status:** ✅ **UŻYWANY** w ProcessPaymentHandler

### PaymentApplicationService (Nowy)
**Funkcje:**
- `processPayment()` - z walidacją domenową i fee calculation
- `refundPayment()` - z walidacją i check refund window
- `getPaymentStatus()` - status płatności
- `calculatePaymentFees()` - kalkulacja opłat

**Status:** ❌ **NIEUŻYWANY** (lepszy ale nie podłączony)

### PaymentDomainService (Nowy)
**Funkcje:**
- `validatePaymentAmount()` - walidacja kwoty
- `validateCurrency()` - walidacja waluty (USD, EUR, GBP, PLN)
- `calculateFees()` - fee calculation (2.5-3.5% + fixed)
- `isRefundable()` - 30-day refund window

**Status:** ✅ **UŻYWANY** przez PaymentApplicationService

### 🔍 DUPLIKACJE:
1. ❌ `processPayment()` - w obu, ale różne implementacje
2. ❌ `refundPayment()` - w obu
3. ⚠️ PaymentService to prosty mock, PaymentApplicationService ma prawdziwą logikę

**REKOMENDACJA:** 
- Zastąpić PaymentService przez PaymentApplicationService
- PaymentApplicationService używa StripePaymentGateway przez interface
- Lepsze separation of concerns

---

## 4. TicketService vs TicketApplicationService + TicketDomainService

### TicketService (Legacy - `src/Service/TicketService.php`)
**Funkcje:**
- `checkTicketAvailability()` - sprawdza dostępność z cache

**Status:** ✅ **UŻYWANY** w TicketController

### TicketApplicationService (Nowy)
**Funkcje:**
- `checkTicketAvailability()` - identyczna jak legacy
- `getEventAvailability()` - dla całego wydarzenia
- `purchaseTicket()` - zakup biletu
- `confirmTicketPurchase()` - potwierdzenie
- `getUserTickets()` - bilety użytkownika z cache
- `cancelTicket()` - anulowanie

**Status:** ❌ **NIEUŻYWANY** (gotowy ale nie podłączony)

### TicketDomainService (Nowy)
**Funkcje:**
- `createTicket()` - czysta logika tworzenia
- `confirmTicketPurchase()` - logika potwierdzenia
- `cancelTicket()` - logika anulowania
- `refundTicket()` - logika zwrotu
- `isTicketTransferable()` - czy można przenieść
- `transferTicket()` - transfer biletu

**Status:** ✅ **UŻYWANY** przez PurchaseTicketHandler

### 🔍 DUPLIKACJE:
1. ❌ `checkTicketAvailability()` - IDENTYCZNA funkcja w obu
2. ❌ Cache logic zduplikowana

**REKOMENDACJA:** Usunąć TicketService, używać TicketApplicationService

---

## 5. NotificationService vs NotificationApplicationService

### NotificationService (Legacy - `src/Service/NotificationService.php`)
**Funkcje:**
- `notifyEventPublished()` - email + Mercure
- `shareOnSocialMedia()` - social sharing + Mercure
- `sendRealTimeUpdate()` - raw Mercure publish
- `publishNotificationToUser()` - user-specific
- `publishGlobalNotification()` - global broadcast
- `notifyEventUpdated()` - update notifications
- `notifyEventCancelled()` - cancellation emails + Mercure

**Dependencies:** EmailService, MercurePublisher

**Status:** ⚠️ **CZĘŚCIOWO UŻYWANY**

### NotificationApplicationService (Nowy)
**Funkcje:**
- `sendEventPublishedNotifications()` - email + MessageBus
- `sendEventCancelledNotifications()` - email + MessageBus
- `shareEventOnSocialMedia()` - MessageBus
- `sendNotificationToUser()` - MessageBus
- `sendGlobalNotification()` - MessageBus

**Dependencies:** EmailServiceInterface, MessageBusInterface

**Status:** ✅ **UŻYWANY** w NotifyEventPublishedHandler i EventController

### 🔍 DUPLIKACJE:
1. ❌ `notifyEventPublished()` vs `sendEventPublishedNotifications()` - prawie identyczne
2. ❌ `notifyEventCancelled()` vs `sendEventCancelledNotifications()` - prawie identyczne
3. ❌ `shareOnSocialMedia()` - w obu
4. ⚠️ Różne podejścia: NotificationService używa MercurePublisher bezpośrednio, NotificationApplicationService używa MessageBusInterface

**REKOMENDACJA:** Usunąć NotificationService, standaryzować na MessageBusInterface

---

## 6. EmailService vs SymfonyMailerAdapter

### EmailService (Legacy - `src/Service/EmailService.php`)
**Funkcje:**
- `sendTicketConfirmation()`
- `sendEventPublishedNotification()`
- `sendEventCancelledNotification()`

**Status:** ✅ **UŻYWANY** w NotificationService

### SymfonyMailerAdapter (Infrastructure - `src/Infrastructure/Email/`)
**Funkcje:** (implementuje EmailServiceInterface)
- `sendTicketConfirmation()`
- `sendEventPublishedNotification()`
- `sendEventCancelledNotification()`
- `sendEmail()` - generic method

**Status:** ✅ **UŻYWANY** jako DI w nowych serwisach

### 🔍 DUPLIKACJE:
1. ❌ IDENTYCZNE funkcje w obu klasach
2. ❌ EmailService i SymfonyMailerAdapter robią to samo

**REKOMENDACJA:** 
- Usunąć EmailService
- Wszędzie używać EmailServiceInterface z SymfonyMailerAdapter
- Lepszy design pattern (Adapter Pattern + Interface)

---

## 7. EventStatisticsService - KONFLIKT NAZW!

### ⚠️ UWAGA: DWA PLIKI O TEJ SAMEJ NAZWIE!

### EventStatisticsService (Legacy - `src/Service/EventStatisticsService.php`)
**Funkcje:**
- `updateEventStatistics()` - zapisuje do cache
- `getEventStatistics()` - pobiera z cache
- `invalidateCache()` - czyści cache
- `calculateStatistics()` - prywatna, prosty wrapper

**Status:** ✅ **UŻYWANY** w UpdateEventStatisticsHandler

### EventStatisticsService (Domain - `src/Domain/Analytics/Service/EventStatisticsService.php`)
**Funkcje:** (ZNACZNIE BARDZIEJ ZAAWANSOWANY)
- `calculateEventStatistics()` - główna z pełnymi statystykami
- `calculateBasicStats()` - capacity, sold, available, occupancy
- `calculateRevenueStats()` - revenue, average, projected
- `calculateSalesTimeline()` - sprzedaż w czasie
- `calculateTicketTypeBreakdown()` - rozłożenie typów
- `calculatePerformanceMetrics()` - conversion, velocity, scores
- Prywatne helpers: occupancyRate, daysUntilEvent, projectedRevenue, etc.

**Status:** ❌ **NIEUŻYWANY** (gotowy ale nie podłączony)

### 🔍 KONFLIKT:
1. ❌ **KRYTYCZNY:** Dwa pliki o tej samej nazwie klasy!
2. ❌ Domain version ma 10x więcej funkcjonalności
3. ❌ Legacy version to tylko cache wrapper
4. ⚠️ PHP autoloader może mieć problemy z tym konfliktem

**REKOMENDACJA:** 
- ZMIENIĆ NAZWĘ jednego z nich
- Legacy: przemianować na `EventStatisticsCacheService`
- Domain: zachować `EventStatisticsService` lub `EventAnalyticsService`
- Zintegrować Domain version z kontrolerem

---

