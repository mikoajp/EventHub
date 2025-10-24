# Kompleksowa Analiza Projektu EventHub

**Data analizy:** 2025-01-XX  
**Projekt:** EventHub - Platforma zarządzania wydarzeniami  
**Stack:** Symfony 7.2 (Backend) + React 18 + TypeScript (Frontend)

---

## 📋 Spis Treści

1. [Podsumowanie Wykonawcze](#podsumowanie-wykonawcze)
2. [Architektura Projektu](#architektura-projektu)
3. [Zidentyfikowane Problemy](#zidentyfikowane-problemy)
4. [Duplikacja Kodu](#duplikacja-kodu)
5. [Nieużywane Funkcjonalności](#nieużywane-funkcjonalności)
6. [Rekomendacje Refaktoringu](#rekomendacje-refaktoringu)
7. [Plan Działania](#plan-działania)

---

## 🎯 Podsumowanie Wykonawcze

### Stan Projektu
Projekt EventHub jest w **średnio-zaawansowanym** stadium rozwoju z częściowo zaimplementowaną architekturą DDD (Domain-Driven Design) i wzorcem CQRS. Występuje znacząca **duplikacja funkcjonalności** między legacy code (`src/Service`) a nową architekturą (`src/Application/Service` + `src/Domain`).

### Kluczowe Statystyki
- **Kontrolery:** 5 (1 health check, 1 auth, 2 API, 1 admin)
- **Serwisy Legacy:** 12 plików w `src/Service/`
- **Serwisy Application Layer:** 5 plików w `src/Application/Service/`
- **Serwisy Domain Layer:** 4 pliki w `src/Domain/*/Service/`
- **Message Handlers:** 7 handlerów (Commands + Events)
- **Encje:** 7 głównych encji (Event, User, Ticket, TicketType, Order, OrderItem, RefreshToken)

### Główne Wnioski
1. ✅ **Mocne strony:**
   - Dobrze zorganizowana struktura DDD w nowych częściach
   - Implementacja CQRS z Symfony Messenger
   - Wykorzystanie RabbitMQ do asynchronicznego przetwarzania
   - Cache z Redis i tag-aware caching
   - JWT authentication z refresh tokens

2. ⚠️ **Problemy:**
   - **Masywna duplikacja** między `Service/` a `Application/Service/`
   - Nieużywane/częściowo używane serwisy legacy
   - Mieszanie warstw odpowiedzialności
   - Logika biznesowa w encjach (Fat Models)
   - Niejednolite podejście do cache'owania

3. 🔄 **Do refactoringu:**
   - Usunięcie lub migracja legacy serwisów
   - Konsolidacja logiki cache
   - Przeniesienie logiki z encji do serwisów domenowych
   - Standaryzacja interfejsów

---

## 📊 Kluczowe Znaleziska

### Duplikacja Funkcjonalności

| Legacy Service | Nowy Service | Status Migracji | Duplikacja |
|----------------|--------------|-----------------|------------|
| EventService | EventApplicationService | ⚠️ Częściowa | 80% |
| AuthService | UserApplicationService | ⚠️ Częściowa | 60% |
| PaymentService | PaymentApplicationService | ❌ Brak | 100% |
| TicketService | TicketApplicationService | ❌ Brak | 40% |
| NotificationService | NotificationApplicationService | ⚠️ Częściowa | 90% |
| EmailService | SymfonyMailerAdapter | ❌ Brak | 100% |
| EventStatisticsService | EventStatisticsService (Domain) | 🔴 KONFLIKT | Różne impl. |

**🔴 KRYTYCZNY:** EventStatisticsService istnieje w dwóch lokalizacjach z różnymi implementacjami!

### Wykorzystanie Komponentów

**✅ W pełni wykorzystane:**
- EventApplicationService + EventDomainService + EventPublishingService
- UserApplicationService + UserDomainService  
- Infrastructure: RedisCacheAdapter, SymfonyMailerAdapter, StripePaymentGateway
- CQRS Handlers: CreateEvent, PublishEvent, PurchaseTicket, ProcessPayment

**⚠️ Częściowo wykorzystane:**
- PaymentApplicationService (gotowy, ale nieużywany w handlerze)
- TicketApplicationService (tylko availability, brak purchase/cancel)
- EventStatisticsService (Domain) (zaawansowane analytics, nieużywane)
- NotificationApplicationService (konkuruje z legacy)

**❌ Nieużywane/Do usunięcia:**
- EventService (kompletnie zastąpiony)
- AuthService (zastąpiony przez UserApplicationService)
- Commands bez handlerów: CancelEventCommand, ReserveTicketCommand

### Brakujące Endpointy API

Gotowe serwisy bez endpointów:
- `POST /api/tickets/purchase` - zakup biletu (jest handler, brak endpointu)
- `GET /api/tickets/my` - moje bilety
- `POST /api/tickets/{id}/cancel` - anulowanie
- `POST /api/payments/{id}/refund` - zwrot płatności
- `GET /api/events/{id}/analytics` - zaawansowane statystyki

---

## 🏗️ Architektura Projektu

### Aktualna Struktura Warstw

```
┌─────────────────────────────────────────────────────┐
│  Presentation Layer (Controllers)                    │
│  - AuthController                                    │
│  - EventController                                   │
│  - TicketController                                  │
└──────────────────┬──────────────────────────────────┘
                   │
        ┌──────────┴──────────┐
        │                     │
┌───────▼──────────┐  ┌──────▼─────────────────┐
│  Legacy Services  │  │ Application Services   │
│  (src/Service/)   │  │ (src/Application/)     │
│                   │  │                        │
│ - EventService    │  │ - EventAppService      │
│ - AuthService     │  │ - TicketAppService     │
│ - TicketService   │  │ - PaymentAppService    │
│ - PaymentService  │  │ - UserAppService       │
│ - EmailService    │  │ - NotificationAppSvc   │
│ - ...             │  │                        │
└───────┬──────────┘  └──────┬─────────────────┘
        │                     │
        │            ┌────────▼────────────────┐
        │            │  Domain Services        │
        │            │  (src/Domain/)          │
        │            │                         │
        │            │ - EventDomainService    │
        │            │ - TicketDomainService   │
        │            │ - PaymentDomainService  │
        │            │ - UserDomainService     │
        │            └────────┬────────────────┘
        │                     │
        └─────────┬───────────┘
                  │
        ┌─────────▼──────────────────────┐
        │  Infrastructure Layer          │
        │  - Cache (Redis)               │
        │  - Email (Symfony Mailer)      │
        │  - Payment (Stripe stub)       │
        │  - Messaging (Mercure/RabbitMQ)│
        │  - Validation                  │
        └────────────────────────────────┘
```

### Problem: Dwie Równoległe Architektury

**LEGACY PATH (stary kod):**
```
Controller → Service → Repository → Database
(Np. TicketController → TicketService → TicketRepository)
```

**DDD PATH (nowy kod):**
```
Controller → ApplicationService → DomainService → Repository → Database
(Np. EventController → EventApplicationService → EventDomainService → EventRepository)
```

**CQRS PATH (dla niektórych operacji):**
```
Controller → MessageBus → Handler → Service → Repository → Database
```

---

