# Analiza Nieużywanych Elementów Projektu - Raport Finalny

**Data:** 2025-01-29
**Autor:** Rovo Dev
**Status:** Kompletna analiza

Aktualizacja 2025-10-24:
- Usunięto legacy: EventStatisticsCacheService (mylnie nazwana klasa), ValidationService, Service/TicketAvailabilityService.
- CacheStatsService zrefaktoryzowano: usunięto zależność od CacheService.
- CacheService usunięto; aplikacja korzysta z Infrastructure\Cache\CacheInterface (RedisCacheAdapter).
- Sekcja o konflikcie EventStatisticsService: ROZWIĄZANA.

---

## Spis Treści
1. [Podsumowanie Wykonawcze](#podsumowanie-wykonawcze)
2. [Nieużywane Application Services](#nieużywane-application-services)
3. [Nieużywane Domain Services](#nieużywane-domain-services)
4. [Duplikaty i Konflikty](#duplikaty-i-konflikty)
5. [Niepełne Implementacje](#niepełne-implementacje)
6. [Legacy Services](#legacy-services)
7. [Rekomendacje Działań](#rekomendacje-działań)

---

## 1. Podsumowanie Wykonawcze

### Statystyki

| Kategoria | Liczba | Status |
|-----------|--------|--------|
| **Nieużywane Services** | 1 | ❌ Do usunięcia |
| **Częściowo używane Services** | 3 | ⚠️ Do implementacji |
| **Konflikty nazw** | 1 | 🔴 KRYTYCZNE |
| **Duplikaty Services** | 6 | ⚠️ Do standaryzacji |
| **Niepełne implementacje** | 4 | 📝 Do dokończenia |

### Najważniejsze Problemy

1. 🔴 **KRYTYCZNY:** Konflikt nazw `EventStatisticsService` (2 różne implementacje)
2. ❌ **PaymentApplicationService** - kompletny, ale całkowicie nieużywany
3. ⚠️ **NotificationApplicationService** - używany tylko w 3 miejscach
4. ⚠️ Duplikacja logiki między legacy Services a nowymi Application Services
5. 📝 Brakujące metody w `EventDomainService` (canBeCancelled, unpublishEvent)

---


## 2. Nieużywane Application Services

### 2.1 ❌ PaymentApplicationService - CAŁKOWICIE NIEUŻYWANY

**Lokalizacja:** `backend/src/Application/Service/PaymentApplicationService.php`

**Status:** Kompletny, dobrze zaprojektowany, ale w 100% nieużywany

**Funkcjonalności:**
- ✅ `processPayment()` - pełna walidacja, kalkulacja opłat, integracja z PaymentGateway
- ✅ `refundPayment()` - z 30-dniowym oknem zwrotu
- ✅ `getPaymentStatus()` - pobieranie statusu płatności
- ✅ `calculatePaymentFees()` - kalkulacja opłat dla różnych walut

**Problem:**
ProcessPaymentHandler używa legacy PaymentService (mock) zamiast PaymentApplicationService

**Gdzie powinien być używany:**
1. ❌ ProcessPaymentHandler - obecnie używa mock PaymentService
2. ❌ Brak endpointu API dla płatności
3. ❌ Brak kontrolera płatności

**Rekomendacja:** 
- **ZACHOWAĆ** - Service jest dobrze zaprojektowany
- **ZAIMPLEMENTOWAĆ** użycie w ProcessPaymentHandler
- **DODAĆ** PaymentController z endpointami

---

### 2.2 ⚠️ NotificationApplicationService - CZĘŚCIOWO UŻYWANY

**Lokalizacja:** `backend/src/Application/Service/NotificationApplicationService.php`

**Status:** Używany tylko w 3 miejscach (z 8 potencjalnych)

**Metody i ich użycie:**

| Metoda | Używana | Gdzie |
|--------|---------|-------|
| sendEventPublishedNotifications() | ✅ | NotifyEventPublishedHandler, PublishEventHandler |
| sendEventCancelledNotifications() | ❌ | Nigdzie |
| shareEventOnSocialMedia() | ✅ | NotifyEventPublishedHandler |
| sendNotificationToUser() | ❌ | Nigdzie |
| sendGlobalNotification() | ✅ | EventController (4 razy) |

**Problem - Duplikacja:**
EventApplicationService ma własną metodę sendEventPublishedNotifications() która robi to samo!

**Rekomendacja:**
- **USUNĄĆ** metodę z EventApplicationService
- **UŻYWAĆ** tylko NotificationApplicationService
- **ZAIMPLEMENTOWAĆ** brakujące użycia w event cancellation

---


### 2.3 ⚠️ UserApplicationService - Zbędne metody formatowania

**Lokalizacja:** `backend/src/Application/Service/UserApplicationService.php`

**Status:** Używany, ale niektóre metody są zbędne

**Metody zbędne (formatowanie):**
- `formatLoginResponse()` - powinno być w Presenterze
- `formatRegistrationResponse()` - powinno być w Presenterze

**Problem - podwójne formatowanie:**
AuthController wywołuje UserPresenter.presentLoginResponse(UserApplicationService.formatLoginResponse())

**Rekomendacja:**
- **PRZENIEŚĆ** logikę formatowania do UserPresenter
- **USUNĄĆ** metody formatLoginResponse() i formatRegistrationResponse()

---

## 3. Nieużywane Domain Services

### 3.1 ❌ EventStatisticsService (Domain/Analytics) - CAŁKOWICIE NIEUŻYWANY

**Lokalizacja:** `backend/src/Domain/Analytics/Service/EventStatisticsService.php`

**Status:** Zaawansowana implementacja analytics, ale w 100% nieużywana

**Funkcjonalności (wszystkie nieużywane):**
- ❌ `calculateEventStatistics()` - kompleksowe statystyki
- ❌ `calculateBasicStats()` - podstawowe statystyki
- ❌ `calculateRevenueStats()` - statystyki przychodów
- ❌ `calculateSalesTimeline()` - timeline sprzedaży
- ❌ `calculateTicketTypeBreakdown()` - breakdown typów biletów
- ❌ `calculatePerformanceMetrics()` - metryki wydajności (conversion rate, sales velocity)

**Dlaczego nieużywany:**
- Brak endpointu API
- Brak kontrolera
- Brak integracji z EventController

**Potencjalna wartość:** WYSOKA - to zaawansowany analytics service

**Rekomendacja:**
- **ZACHOWAĆ** - wartościowa implementacja
- **ZAIMPLEMENTOWAĆ** endpoint: `GET /api/events/{id}/analytics`
- **DODAĆ** do EventController

---

### 3.2 ⚠️ EventDomainService - NIEKOMPLETNY

**Lokalizacja:** `backend/src/Domain/Event/Service/EventDomainService.php`

**Status:** Używany, ale brakuje metod

**Metody istniejące:**
- ✅ `createEvent()`
- ✅ `updateEvent()`
- ✅ `canUserModifyEvent()`
- ✅ `isEventPublishable()`

**Metody BRAKUJĄCE (wywoływane, ale nie istnieją):**
`php
// EventApplicationService wywołuje te metody, ale ich NIE MA!
->eventDomainService->canBeCancelled();      // ❌ NIE ISTNIEJE
->eventDomainService->unpublishEvent();      // ❌ NIE ISTNIEJE  
->eventDomainService->canBeUnpublished();    // ❌ NIE ISTNIEJE
->eventDomainService->cancelEvent();         // ❌ NIE ISTNIEJE
`

**Obecna sytuacja:**
Logika jest w Entity Event (canBeCancelled, canBeUnpublished) i EventPublishingService (cancelEvent)

**Rekomendacja:**
- **DODAĆ** brakujące metody do EventDomainService
- **PRZENIEŚĆ** logikę z Entity do Domain Service (zgodnie z DDD)

---


### 3.3 ⚠️ TicketDomainService - CZĘŚCIOWO NIEUŻYWANY

**Lokalizacja:** `backend/src/Domain/Ticket/Service/TicketDomainService.php`

**Status:** 50% metod nieużywanych

**Metody używane:**
- ✅ `createTicket()` - używane w TicketApplicationService
- ✅ `confirmTicketPurchase()` - używane w TicketApplicationService
- ✅ `cancelTicket()` - używane w TicketApplicationService

**Metody NIEUŻYWANE:**
- ❌ `refundTicket()` - tylko w Voterze jako check
- ❌ `isTicketTransferable()` - tylko w Voterze jako check
- ❌ `transferTicket()` - nigdzie nieużywane

**Rekomendacja:**
- **ZACHOWAĆ** wszystkie metody (mogą być przydatne w przyszłości)
- **ZAIMPLEMENTOWAĆ** endpointy:
  - `POST /api/tickets/{id}/transfer`
  - `POST /api/tickets/{id}/refund`

---

### 3.4 ⚠️ TicketAvailabilityService (Domain) - Duplikat legacy

**Lokalizacja:** `backend/src/Domain/Ticket/Service/TicketAvailabilityService.php`

**Status:** Używany, ale istnieje duplikat w src/Service

**Problem:**
Istnieją DWA serwisy o tej samej nazwie:
1. `backend/src/Domain/Ticket/Service/TicketAvailabilityService.php` - pełny
2. `backend/src/Service/TicketAvailabilityService.php` - uproszczony legacy

**Metody w Domain (używane):**
- ✅ `isAvailable()` - używane w TicketApplicationService
- ✅ `getAvailableQuantity()` - używane wewnętrznie
- ✅ `checkEventAvailability()` - używane w TicketApplicationService
- ⚠️ `reserveTickets()` - zaimplementowane, ale tylko return true (TODO)

**Rekomendacja:**
- **USUNĄĆ** legacy Service/TicketAvailabilityService.php
- **ZACHOWAĆ** tylko Domain version
- **ZAIMPLEMENTOWAĆ** prawdziwą logikę w reserveTickets() z expiration

---

## 4. Duplikaty i Konflikty

### 4.1 🔴 KRYTYCZNY: EventStatisticsService - KONFLIKT NAZW

**Problem:** Dwie różne klasy o tej samej nazwie!

**Wersja 1: Legacy (src/Service/EventStatisticsCacheService.php)**
- Klasa nazywa się EventStatisticsService (błąd w nazwie pliku!)
- Proste cache wrapper
- Metody: updateEventStatistics(), getEventStatistics(), invalidateCache()
- **Używana w:** UpdateEventStatisticsHandler

**Wersja 2: Domain (src/Domain/Analytics/Service/EventStatisticsService.php)**
- Zaawansowany analytics service
- Wiele metod kalkulacji (revenue, performance, timeline)
- **Nieużywana nigdzie!**

**Wpływ:**
- Handler używa LEGACY wersji przez `use App\Service\EventStatisticsService`
- Konflikt namespace może prowadzić do błędów

**Rekomendacja PRIORYTET #1:**
1. **PRZEMIANOWAĆ** plik legacy na EventStatisticsCacheService.php
2. **POPRAWIĆ** nazwę klasy na EventStatisticsCacheService
3. **ZAKTUALIZOWAĆ** import w UpdateEventStatisticsHandler
4. **ZACHOWAĆ** Domain version jako EventStatisticsService lub EventAnalyticsService

---


### 4.2 ⚠️ Duplikacja: CacheService vs RedisCacheAdapter

**Problem:** Dwa podobne serwisy do obsługi cache

**CacheService (src/Service/CacheService.php)**
- Legacy implementation
- Używany przez CacheStatsService
- Bezpośrednia implementacja, nie przez interface

**RedisCacheAdapter (src/Infrastructure/Cache/RedisCacheAdapter.php)**
- Nowa implementacja zgodna z DDD
- Implementuje CacheInterface
- Używana przez Application Services przez interface

**Obecne użycie:**
- Application Services → CacheInterface (RedisCacheAdapter) ✅
- CacheStatsService → CacheService (legacy) ⚠️
- CacheController → CacheStatsService → CacheService ⚠️

**Rekomendacja:**
- **REFAKTORYZOWAĆ** CacheStatsService do użycia CacheInterface
- **USUNĄĆ** legacy CacheService po migracji
- **ZACHOWAĆ** tylko RedisCacheAdapter

---

### 4.3 ⚠️ Duplikacja: ValidationService vs RequestValidatorInterface

**Problem:** Dwa sposoby walidacji

**ValidationService (src/Service/ValidationService.php)**
- Legacy
- Jedna metoda: validateAndCreateEventDTO()
- Nieużywany (EventController używa Infrastructure)

**SymfonyRequestValidator (src/Infrastructure/Validation/SymfonyRequestValidator.php)**
- Nowa implementacja przez interface
- Używana przez AuthController

**Rekomendacja:**
- **USUNĄĆ** ValidationService jako nieużywany
- **UŻYWAĆ** tylko RequestValidatorInterface

---

### 4.4 ⚠️ Duplikacja logiki w Application/Command i MessageHandler

**Problem:** Zduplikowane handlery

**Application/Command/Event/**
- CreateEventHandler - minimalna implementacja (TODO)
- PublishEventHandler - minimalna implementacja

**MessageHandler/Command/Event/**  
- CreateEventHandler - pełna implementacja ✅
- PublishEventHandler - pełna implementacja ✅

**Rekomendacja:**
- **USUNĄĆ** Application/Command handlers (są niepełne)
- **ZACHOWAĆ** tylko MessageHandler versions

---

## 5. Niepełne Implementacje

### 5.1 📝 Application/Command Handlers - TODO

**Lokalizacja:** `backend/src/Application/Command/Event/`

**Status:** Zaślepki z komentarzem TODO

`php
// CreateEventHandler.php
public function __invoke(CreateEventCommand ): string
{
    return ->em->wrapInTransaction(function() use () {
        // TODO: create and persist Event aggregate
         = (string) \Symfony\Component\Uid\Uuid::v4();
        return ;
    });
}
`

**Rekomendacja:**
- **USUNĄĆ** te pliki (są zduplikowane z MessageHandler)
- Używać tylko MessageHandler/Command/Event handlers

---

### 5.2 📝 TicketAvailabilityService.reserveTickets() - TODO

**Lokalizacja:** `backend/src/Domain/Ticket/Service/TicketAvailabilityService.php`

**Status:** Implementacja zaślepka

`php
public function reserveTickets(TicketType , int ): bool
{
    if (!->isAvailable(, )) {
        return false;
    }
    // In a real implementation, you might want to create temporary reservations
    // that expire after a certain time (e.g., 15 minutes)
    return true;
}
`

**Rekomendacja:**
- **ZAIMPLEMENTOWAĆ** prawdziwe rezerwacje z timeoutem
- Dodać entity ReservationLock z expiration
- Dodać background job do czyszczenia wygasłych rezerwacji

---


### 5.3 📝 Event.getAttendees() - Nieużywane pole

**Lokalizacja:** `backend/src/Entity/Event.php`

**Status:** Pole ManyToMany z Collection attendees

**Problem:**
- Pole istnieje w Entity
- Używane tylko w 2 miejscach:
  - NotificationApplicationService.sendEventCancelledNotifications()
  - EventController.getAttendees() endpoint

**Brak logiki zarządzania:**
- Brak automatycznego dodawania attendees po zakupie biletu
- Brak synchronizacji z ticketami
- Niejednoznaczne znaczenie (purchasers? reserved? interested?)

**Rekomendacja:**
- **OPCJA A:** Usunąć pole jako nieużywane/niepełne
- **OPCJA B:** Zaimplementować pełną logikę:
  - Auto-add attendee po zakupie biletu
  - Metoda w TicketDomainService
  - Clear separation: tickets = purchased, attendees = confirmed attendees

---

### 5.4 📝 Entity logic vs Domain Services - Niespójność

**Problem:** Logika biznesowa rozrzucona między Entity a Domain Services

**W Entity Event:**
- `canBeCancelled()` ✅
- `canBeUnpublished()` ✅
- `canBePublished()` ✅

**W Domain Services:**
- EventDomainService NIE MA tych metod ❌
- EventApplicationService wywołuje je na Entity bezpośrednio

**Wg DDD powinno być:**
`php
// Domain Service should encapsulate business rules
EventDomainService::canBeCancelled(Event ): bool
EventDomainService::canBeUnpublished(Event ): bool
`

**Rekomendacja:**
- **PRZENIEŚĆ** logikę z Entity do EventDomainService
- Entity powinno być tylko data container
- Wszystkie business rules w Domain Services

---

## 6. Legacy Services do Usunięcia

### 6.1 ❌ PaymentService (mock)

**Lokalizacja:** Nie istnieje w src/Service/ (jest tylko import)

**Status:** Mock używany w ProcessPaymentHandler

**Rekomendacja:** USUNĄĆ i zastąpić PaymentApplicationService

---

### 6.2 ❌ NotificationService

**Lokalizacja:** Nie istnieje w src/Service/ (jest tylko w analizach)

**Status:** Legacy, zastąpiony przez NotificationApplicationService

**Rekomendacja:** Już usunięty lub nigdy nie istniał

---

### 6.3 ⚠️ ErrorHandlerService - Używany

**Lokalizacja:** `backend/src/Service/ErrorHandlerService.php`

**Status:** Używany we wszystkich kontrolerach

**Rekomendacja:** ZACHOWAĆ (nie jest legacy, jest utility service)

---

## 7. Rekomendacje Działań

### Priorytet 1 - KRYTYCZNE (do natychmiastowej naprawy)

#### 1.1 Konflikt EventStatisticsService ⏱️ 30 min

`ash
# 1. Przemianować plik
git mv backend/src/Service/EventStatisticsCacheService.php \
       backend/src/Service/EventStatisticsCacheService.php

# 2. W pliku zmienić nazwę klasy
class EventStatisticsCacheService { ... }

# 3. Zaktualizować import w UpdateEventStatisticsHandler
use App\Service\EventStatisticsCacheService;
`

**Wpływ:** Rozwiązuje konflikt namespace, zapobiega błędom

---


### Priorytet 2 - WYSOKIE (do zrobienia w tym sprincie)

#### 2.1 Zaimplementować PaymentApplicationService ⏱️ 4h

**Kroki:**
1. Zastąpić PaymentService w ProcessPaymentHandler
2. Dodać PaymentController z endpointami:
   - POST /api/payments/process
   - POST /api/payments/{id}/refund  
   - GET /api/payments/{id}/status
   - POST /api/payments/fees/calculate

**Kod:**
`php
// ProcessPaymentHandler.php
class ProcessPaymentHandler {
    public function __construct(
        private PaymentApplicationService , // ✅
        // ...
    ) {}
    
    public function __invoke(ProcessPaymentCommand ): void
    {
         = ->paymentApplicationService->processPayment(
            ->paymentMethodId,
            ->amount,
            ->currency,
            
        );
    }
}
`

**Wpływ:** PaymentApplicationService stanie się używany, usunie mock

---

#### 2.2 Uzupełnić EventDomainService ⏱️ 2h

**Dodać metody:**
`php
// EventDomainService.php
public function canBeCancelled(Event ): bool
{
    return !->isCancelled() && !->isCompleted();
}

public function canBeUnpublished(Event ): bool  
{
    return ->isPublished() && ->getTicketsSold() === 0;
}

public function cancelEvent(Event ): void
{
    ->setStatus(Event::STATUS_CANCELLED);
    ->setCancelledAt(new \DateTimeImmutable());
    ->entityManager->flush();
}

public function unpublishEvent(Event ): void
{
    ->setStatus(Event::STATUS_DRAFT);
    ->setPublishedAt(null);
    ->entityManager->flush();
}
`

**Wpływ:** Kompletny EventDomainService zgodny z DDD

---

#### 2.3 Usunąć duplikaty NotificationApplicationService ⏱️ 1h

**Kroki:**
1. Usunąć metodę sendEventPublishedNotifications z EventApplicationService
2. W EventApplicationService.publishEvent() użyć NotificationApplicationService

`php
// EventApplicationService.php
public function publishEvent(Event , User ): void
{
    if (!->eventDomainService->isEventPublishable()) {
        throw new \InvalidArgumentException('Event is not publishable');
    }

    ->eventPublishingService->publishEvent(, );
    
    // Deleguj do NotificationApplicationService
    ->notificationApplicationService->sendEventPublishedNotifications();
    
    ->cache->deletePattern('events.*');
}

// USUŃ prywatną metodę sendEventPublishedNotifications()
`

**Wpływ:** Eliminuje duplikację, standaryzuje notifications

---

### Priorytet 3 - ŚREDNIE (następny sprint)

#### 3.1 Zaimplementować EventStatisticsService (Domain) ⏱️ 4h

**Dodać endpoint:**
`php
// EventController.php
#[Route('/{id}/analytics', methods: ['GET'])]
public function getAnalytics(string ): JsonResponse
{
     = ->eventApplicationService->getEventById();
     = ->eventStatisticsService->calculateEventStatistics();
    return ->json();
}
`

**Wpływ:** EventStatisticsService (Domain) stanie się używany

---

#### 3.2 Refaktoryzacja CacheStatsService ⏱️ 2h

**Zmienić z:**
`php
class CacheStatsService {
    public function __construct(private CacheService ) {}
}
`

**Na:**
`php
class CacheStatsService {
    public function __construct(private CacheInterface ) {}
}
`

**Wpływ:** Usunie zależność od legacy CacheService

---


#### 3.3 Usunąć niepotrzebne pliki ⏱️ 1h

**Do usunięcia:**
1. `backend/src/Service/ValidationService.php` - zastąpiony przez RequestValidatorInterface
2. `backend/src/Service/TicketAvailabilityService.php` - duplikat Domain version
3. `backend/src/Application/Command/Event/CreateEventCommand.php` - TODO zaślepka
4. `backend/src/Application/Command/Event/CreateEventHandler.php` - TODO zaślepka
5. `backend/src/Application/Command/Event/PublishEventCommand.php` - zduplikowany
6. `backend/src/Application/Command/Event/PublishEventHandler.php` - zduplikowany

**Komenda:**
`ash
rm backend/src/Service/ValidationService.php
rm backend/src/Service/TicketAvailabilityService.php
rm -r backend/src/Application/Command/
rm -r backend/src/Application/Query/ # tylko jeśli puste
`

**Wpływ:** Czystszy kod, mniej zduplikowanych plików

---

#### 3.4 Przenieść formatowanie do Presenterów ⏱️ 2h

**UserApplicationService:**
- Usunąć formatLoginResponse()
- Usunąć formatRegistrationResponse()

**UserPresenter:**
- Dodać logikę formatowania bezpośrednio w presentLoginResponse()
- Dodać logikę w presentRegistrationResponse()

**AuthController:**
`php
// Przed:
->userPresenter->presentLoginResponse(
    ->userApplicationService->formatLoginResponse()
)

// Po:
->userPresenter->presentLoginResponse()
`

**Wpływ:** Separation of concerns, Presenter odpowiada za formatowanie

---

### Priorytet 4 - NISKIE (backlog)

#### 4.1 Zaimplementować transfer i refund biletów ⏱️ 8h

**Endpointy:**
- POST /api/tickets/{id}/transfer
- POST /api/tickets/{id}/refund

**Użyje istniejących metod:**
- TicketDomainService.transferTicket()
- TicketDomainService.refundTicket()

---

#### 4.2 Zaimplementować reserveTickets() z timeoutem ⏱️ 6h

**Kroki:**
1. Dodać entity ReservationLock (ticket_type_id, quantity, expires_at)
2. Zaimplementować prawdziwą logikę w reserveTickets()
3. Dodać background job do czyszczenia wygasłych rezerwacji

---

#### 4.3 Rozwiązać problem Event.attendees ⏱️ 4h

**Opcja A: Usunąć**
- Jeśli niepotrzebne, po prostu usunąć pole

**Opcja B: Zaimplementować**
- Auto-add attendee w TicketDomainService.confirmTicketPurchase()
- Auto-remove w TicketDomainService.cancelTicket()
- Synchronizacja z biletami

---

## 8. Podsumowanie Czasowe

### Podział pracy

| Priorytet | Zadania | Czas | Wpływ |
|-----------|---------|------|-------|
| **P1 - Krytyczne** | Konflikt EventStatisticsService | 30 min | 🔴 Wysokie |
| **P2 - Wysokie** | PaymentApplicationService + EventDomainService + Duplikaty | 7h | 🟠 Wysokie |
| **P3 - Średnie** | Analytics + Refactoring + Cleanup | 9h | 🟡 Średnie |
| **P4 - Niskie** | Dodatkowe features | 18h | 🟢 Niskie |
| **RAZEM** | | **34.5h** | |

### Quick Wins (< 2h każdy)

1. ✅ Konflikt EventStatisticsService (30 min)
2. ✅ Usunąć niepotrzebne pliki (1h)
3. ✅ Usunąć duplikaty NotificationApplicationService (1h)
4. ✅ Refaktoryzacja CacheStatsService (2h)

**Suma Quick Wins: 4.5h**

---

## 9. Wnioski

### Co działa dobrze ✅

1. **Application Services** - dobrze zaprojektowane (EventApplicationService, TicketApplicationService)
2. **Infrastructure layer** - czysta separacja (PaymentGateway, Cache, Email)
3. **Presenters** - dobrze oddzielone od logiki biznesowej
4. **Message Handlers** - kompletne i działające

### Główne problemy ⚠️

1. **Konflikt nazw** - EventStatisticsService (KRYTYCZNE)
2. **Nieużywane serwisy** - PaymentApplicationService, EventStatisticsService (Domain)
3. **Duplikacje** - Notifications, Cache, Validation
4. **Niepełne implementacje** - EventDomainService, Application/Command handlers
5. **Legacy pozostałości** - stare Services konkurujące z nowymi

### Rekomendacje strategiczne 🎯

1. **Standaryzować na nowych Application Services** - usunąć legacy
2. **Dokończyć Domain Services** - uzupełnić EventDomainService
3. **Zaimplementować nieużywane serwisy** - nie kasować, tylko użyć!
4. **Cleanup** - usunąć zduplikowane i TODO pliki

---

## 10. Plan Działania (Recommended Order)

### Sprint 1 (2 tygodnie) - Foundation

**Tydzień 1:**
1. ✅ Naprawić konflikt EventStatisticsService (30 min)
2. ✅ Uzupełnić EventDomainService (2h)
3. ✅ Usunąć niepotrzebne pliki (1h)
4. ✅ Refaktoryzacja CacheStatsService (2h)

**Tydzień 2:**
5. ✅ Zaimplementować PaymentApplicationService (4h)
6. ✅ Usunąć duplikaty NotificationApplicationService (1h)
7. ✅ Przenieść formatowanie do Presenterów (2h)

**Total Sprint 1: 12.5h**

### Sprint 2 (2 tygodnie) - Features

8. ✅ Zaimplementować EventStatisticsService endpoint (4h)
9. ✅ Zaimplementować transfer/refund biletów (8h)
10. ✅ Zaimplementować reserveTickets z timeoutem (6h)

**Total Sprint 2: 18h**

### Sprint 3 (1 tydzień) - Polish

11. ✅ Rozwiązać Event.attendees (4h)
12. ✅ Code review i testy
13. ✅ Dokumentacja

---

## 11. Ryzyka

| Ryzyko | Prawdopodobieństwo | Wpływ | Mitigacja |
|--------|-------------------|-------|-----------|
| Breaking changes w API | Średnie | Wysokie | Wersjonowanie API, testy |
| Konflikt merge | Niskie | Średnie | Komunikacja w zespole |
| Nieprzewidziane zależności | Średnie | Średnie | Dokładne code review przed usunięciem |
| Brak testów | Wysokie | Wysokie | Dodać testy przed refactoringiem |

---

**Koniec analizy**

Data wygenerowania: 2025-01-29
Wersja: 1.0
Status: Gotowe do implementacji
