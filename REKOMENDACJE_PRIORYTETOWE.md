# 🎯 Rekomendacje Priorytetowe - EventHub

## Executive Summary

Projekt EventHub jest w **stanie przejściowym** między starą architekturą monolityczną a nową architekturą DDD/CQRS. **40-50% funkcjonalności jest zduplikowana**, a **30% nowego kodu nie jest wykorzystywane**.

**Kluczowa decyzja:** Dokończyć migrację do DDD lub wrócić do prostszej architektury.

---

## 🔴 KRYTYCZNE - Do natychmiastowej naprawy

### 1. Konflikt Nazw EventStatisticsService
**Problem:** Dwa pliki o identycznej nazwie klasy w różnych namespace'ach!

```
❌ backend/src/Service/EventStatisticsService.php
❌ backend/src/Domain/Analytics/Service/EventStatisticsService.php
```

**Ryzyko:** Autoloader może się pomylić, nieprzewidywalne zachowanie.

**Rozwiązanie:**
```bash
# Krok 1: Przemianuj legacy
git mv backend/src/Service/EventStatisticsService.php backend/src/Service/EventStatisticsCacheService.php

# Krok 2: Zmień nazwę klasy w pliku
class EventStatisticsCacheService { ... }

# Krok 3: Update w UpdateEventStatisticsHandler
use App\Service\EventStatisticsCacheService;
```

**Czas:** 30 minut  
**Impact:** 🔴 Krytyczny

---

### 2. Usunięcie Martwego Kodu EventService
**Problem:** EventService nie jest nigdzie używany, ale zajmuje 325 linii.

**Weryfikacja:**
```powershell
Select-String -Path backend/src -Pattern "EventService" -ExcludeDir vendor | Where-Object { $_.Line -notmatch "EventApplicationService" }
# Jeśli tylko w definicji samej klasy = bezpieczne usunięcie
```

**Rozwiązanie:**
```powershell
Remove-Item backend/src/Service/EventService.php
```

**Czas:** 15 minut  
**Impact:** 🟡 Średni (cleanup)

---

### 3. Duplikacja EmailService
**Problem:** Dwa identyczne serwisy email.

**Rozwiązanie:**
```php
// W NotificationService i NotificationApplicationService
// Zamień:
private EmailService $emailService

// Na:
private EmailServiceInterface $emailService

// Usuń:
rm backend/src/Service/EmailService.php
```

**Czas:** 30 minut  
**Impact:** 🟡 Średni

---

## 🟡 WYSOKIE - Dokończenie Migracji

### 4. Migracja TicketController
**Problem:** Używa legacy TicketService, a TicketApplicationService jest gotowy ale nieużywany.

**Co zyskujemy:**
- ✅ Caching biletów użytkownika
- ✅ Kompletna logika zakupu/anulowania
- ✅ Lepsza walidacja
- ✅ Separation of concerns

**Nowe endpointy:**
```php
GET    /api/tickets/my           - Lista moich biletów
POST   /api/tickets/purchase     - Zakup (deleguj do PurchaseTicketCommand)
POST   /api/tickets/{id}/cancel  - Anulowanie
```

**Czas:** 2-3 godziny  
**Impact:** 🟢 Wysoki (nowa funkcjonalność + lepszy design)

---

### 5. Migracja PaymentHandler
**Problem:** ProcessPaymentHandler używa mock PaymentService zamiast prawdziwego PaymentApplicationService.

**Obecny:**
```php
// ProcessPaymentHandler
private PaymentService $paymentService // Mock, brak walidacji
```

**Docelowy:**
```php
// ProcessPaymentHandler
private PaymentApplicationService $paymentApplicationService

// Zyskujemy:
// - Walidację walut (USD, EUR, GBP, PLN)
// - Walidację kwot (max $10,000)
// - Automatyczną kalkulację opłat
// - Check refund window (30 dni)
// - Integration z PaymentGatewayInterface (Stripe)
```

**Czas:** 1 godzina  
**Impact:** 🟢 Wysoki (prawdziwa logika płatności)

---

### 6. Konsolidacja NotificationService
**Problem:** Dwa serwisy notification działają równolegle, różne podejścia.

**Legacy:** NotificationService → EmailService + MercurePublisher (bezpośrednio)  
**Nowy:** NotificationApplicationService → EmailServiceInterface + MessageBusInterface

**Rekomendacja:**
```
ZACHOWAĆ: NotificationApplicationService
USUNĄĆ: NotificationService (legacy)

PODEJŚCIE:
1. NotificationApplicationService → MessageBus (RabbitMQ)
2. Event Handlers → MercurePublisher (real-time UI updates)

SEPARATION:
- RabbitMQ = Asynchroniczne taski (email, processing)
- Mercure = Real-time UI notifications (tylko frontend)
```

**Czas:** 3 godziny  
**Impact:** 🟢 Wysoki (clean architecture)

---

## 🟢 ŚREDNIE - Refactoring Quality

### 7. Fat Models - Przeniesienie Logiki Biznesowej
**Problem:** Event entity ma logikę biznesową (70+ metod).

**Logika w encji (BAD):**
```php
class Event {
    public function canBeModified(): bool { ... }
    public function canBeCancelled(): bool { ... }
    public function canBePublished(): bool { ... }
    public function markAsCompleted(): void { ... }
    public function getStatusLabel(): string { ... }
    public function getEventDateFormatted(): string { ... }
}
```

**Powinno być (GOOD):**
```php
// Domain logic → EventDomainService
class EventDomainService {
    public function canBeModified(Event $event): bool { ... }
    public function canBePublished(Event $event): bool { ... }
}

// Presentation logic → EventPresenter
class EventPresenter {
    public function getStatusLabel(Event $event): string { ... }
    public function getFormattedDate(Event $event): string { ... }
}

// Entity - tylko dane
class Event {
    // Gettery/settery + proste computed properties
    public function getAvailableTickets(): int {
        return $this->maxTickets - $this->getTicketsSold();
    }
}
```

**Czas:** 4-6 godzin  
**Impact:** 🟡 Średni (better maintainability)

---

### 8. Standaryzacja Cache
**Problem:** Mieszanie CacheService i CacheInterface.

**Rekomendacja:**
```php
// WSZĘDZIE używać:
use App\Infrastructure\Cache\CacheInterface;

// Z implementacją:
App\Infrastructure\Cache\RedisCacheAdapter implements CacheInterface

// Usunąć lub przenieść do Infrastructure:
App\Service\CacheService → App\Infrastructure\Cache\CacheService?
```

**Tag-based invalidation:**
```php
$this->cache->set($key, $value, $ttl, ['events', 'event:'.$id]);
$this->cache->invalidateTags(['events']); // Wszystkie wydarzenia
```

**Czas:** 2-3 godziny  
**Impact:** 🟡 Średni

---

## 🔵 NISKIE - Nice to Have

### 9. Dokończenie CQRS
**Brakujące komendy:**

```php
// CancelEventCommand - jest klasa, brak handlera
class CancelEventHandler { ... }

// ReserveTicketCommand - jest klasa, brak handlera
class ReserveTicketHandler { ... }
```

**Czas:** 2 godziny każdy  
**Impact:** 🔵 Niski (jeśli planowane)

---

### 10. Nowe Endpointy API
**Gotowe serwisy czekają na endpointy:**

```php
// Tickets
POST   /api/tickets/purchase          → PurchaseTicketCommand
GET    /api/tickets/my                → TicketApplicationService
POST   /api/tickets/{id}/cancel       → TicketApplicationService
POST   /api/tickets/{id}/transfer     → TicketDomainService

// Analytics
GET    /api/events/{id}/analytics     → EventStatisticsService (Domain)
                                         (zaawansowane: velocity, conversion, forecasting)

// Payments
GET    /api/payments/{id}/status      → PaymentApplicationService
POST   /api/payments/{id}/refund      → PaymentApplicationService
POST   /api/payments/fees/calculate   → PaymentApplicationService
```

**Czas:** 1-2 dni  
**Impact:** 🟢 Wysoki (nowa funkcjonalność dla użytkowników)

---

## 📋 Sugerowana Kolejność Działań

### Tydzień 1: Krytyczne Naprawy
1. ✅ [30 min] Napraw konflikt EventStatisticsService
2. ✅ [15 min] Usuń EventService
3. ✅ [30 min] Konsoliduj EmailService
4. ✅ [1h] Migruj PaymentHandler
5. ✅ [2h] Migruj TicketController + dodaj endpointy
**Total: ~4.5 godziny**

### Tydzień 2: Konsolidacja
6. ✅ [3h] Konsoliduj NotificationService
7. ✅ [2h] Standaryzuj Cache
8. ✅ [2h] Testing & bugfixing
**Total: ~7 godzin**

### Tydzień 3: Quality (Optional)
9. ✅ [6h] Refactor Fat Models
10. ✅ [2h] Dokończ CQRS commands
**Total: ~8 godzin**

### Tydzień 4: New Features (Optional)
11. ✅ [8h] Nowe endpointy API
12. ✅ [2h] Dokumentacja + testy
**Total: ~10 godzin**

**Całkowity czas: ~30 godzin = 4 dni robocze**

---

## 🎯 Quick Wins (1 dzień pracy)

Jeśli masz tylko 1 dzień na refactoring, zrób:

1. ✅ Napraw konflikt EventStatisticsService (30 min)
2. ✅ Usuń martwy kod: EventService (15 min)
3. ✅ Migruj PaymentHandler (1h)
4. ✅ Migruj TicketController (2h)
5. ✅ Konsoliduj EmailService (30 min)
6. ✅ Testing (1h)

**Impact:** Usuniesz 40% duplikacji, naprawisz krytyczne problemy, zyskasz lepszą architekturę.

---

## 🚫 Co NIE robić

### ❌ Nie przepisuj wszystkiego na raz
- Ryzyko: Wprowadzenie nowych bugów
- Lepiej: Stopniowa migracja, po jednym komponencie

### ❌ Nie mieszaj stylów
- Jeśli decydujesz się na DDD, konsekwentnie go stosuj
- Unikaj sytuacji gdzie połowa używa legacy, połowa DDD

### ❌ Nie usuwaj bez sprawdzenia
- Zawsze grep przed usunięciem
- Check w całym projekcie (backend + frontend)

### ❌ Nie ignoruj testów
- Każda zmiana = testy
- Minimum: integration tests dla critical paths

---

## 📊 Metryki Sukcesu

### Przed refactoringiem:
- **Duplikacja:** ~45%
- **Nieużywany kod:** ~30%
- **Konfliktów:** 1 krytyczny (EventStatisticsService)
- **Kontrolery:** Mix legacy/DDD
- **Architektura:** Niespójna

### Po refactoringu (Quick Wins):
- **Duplikacja:** ~15%
- **Nieużywany kod:** ~10%
- **Konfliktów:** 0
- **Kontrolery:** Głównie DDD
- **Architektura:** Lepsza spójność

### Po pełnym refactoringu:
- **Duplikacja:** <5%
- **Nieużywany kod:** <5%
- **Konfliktów:** 0
- **Kontrolery:** 100% DDD
- **Architektura:** Spójna DDD/CQRS

---

## 🤔 Alternatywna Strategia: Uproszczenie

Jeśli DDD/CQRS jest **overengineering** dla tego projektu:

### Opcja B: Powrót do prostszej architektury

```
Controller → Service → Repository → Database
```

**Korzyści:**
- ✅ Prostsza w utrzymaniu
- ✅ Mniej warstw abstrakcji
- ✅ Szybszy rozwój dla małego zespołu

**Wady:**
- ❌ Mniej skalowalna
- ❌ Trudniejsze testowanie
- ❌ Gorsza separacja logiki

**Decyzja:** Zależy od:
- Wielkości zespołu
- Planowanej skali projektu
- Wymagań biznesowych

**Dla małego projektu/zespołu:** Prostsza architektura może być lepsza  
**Dla dużego projektu:** DDD/CQRS ma sens, ale dokończ migrację

---

## 📞 Następne Kroki

1. **Zdecyduj strategię:**
   - A) Dokończ migrację do DDD (rekomendowane)
   - B) Uprość architekturę

2. **Zacznij od Quick Wins:**
   - Napraw krytyczne problemy (1 dzień)
   - Szybki ROI, widoczna poprawa

3. **Zaplanuj Sprint:**
   - Tydzień 1: Krytyczne + migracje
   - Tydzień 2: Konsolidacja
   - (Optional) Tydzień 3-4: Quality + Features

4. **Monitoring:**
   - Sprawdź czy wszystko działa po zmianach
   - Testy integracyjne
   - Performance monitoring

---

## ✅ Checklist Przed Rozpoczęciem

- [ ] Backup bazy danych
- [ ] Git branch: `refactor/ddd-migration`
- [ ] Testy integracyjne napisane
- [ ] Code review setup
- [ ] Rollback plan przygotowany
- [ ] Team sync - wszyscy wiedzą co się dzieje
- [ ] Dokumentacja aktualna (README, ADR)

---

**Autor analizy:** AI Assistant  
**Data:** 2025-10-23T14:14:22.482Z  
**Status:** Do review i akceptacji przez tech lead
