# EventHub - Nowa Architektura Serwisów

## 🏗️ Przegląd Architektury

Projekt został zrefaktoryzowany zgodnie z zasadami **Domain-Driven Design (DDD)** i **Clean Architecture**.

## 📁 Struktura Katalogów

```
src/
├── Domain/                     # Logika biznesowa
│   ├── Event/
│   │   └── Service/
│   │       ├── EventDomainService.php
│   │       └── EventPublishingService.php
│   ├── User/
│   │   └── Service/
│   │       └── UserDomainService.php
│   ├── Ticket/
│   │   └── Service/
│   │       ├── TicketDomainService.php
│   │       └── TicketAvailabilityService.php
│   ├── Payment/
│   │   └── Service/
│   │       └── PaymentDomainService.php
│   └── Analytics/
│       └── Service/
│           └── EventStatisticsService.php
├── Application/                # Warstwa aplikacji
│   └── Service/
│       ├── EventApplicationService.php
│       ├── NotificationApplicationService.php
│       ├── UserApplicationService.php
│       ├── TicketApplicationService.php
│       └── PaymentApplicationService.php
├── Infrastructure/             # Implementacje techniczne
│   ├── Cache/
│   │   ├── CacheInterface.php
│   │   └── RedisCacheAdapter.php
│   ├── Messaging/
│   │   ├── MessageBusInterface.php
│   │   └── RabbitMQAdapter.php
│   ├── Email/
│   │   ├── EmailServiceInterface.php
│   │   └── SymfonyMailerAdapter.php
│   ├── Payment/
│   │   ├── PaymentGatewayInterface.php
│   │   └── StripePaymentGateway.php
│   ├── Validation/
│   │   ├── RequestValidatorInterface.php
│   │   └── SymfonyRequestValidator.php
│   └── Monitoring/
│       └── CacheMonitoringService.php
└── Service/                    # Legacy serwisy (do usunięcia)
```

## 🎯 Zasady Architektury

### 1. **Separation of Concerns**
- **Domain Services**: Czysta logika biznesowa
- **Application Services**: Orchestracja i koordynacja
- **Infrastructure**: Implementacje techniczne

### 2. **Dependency Inversion**
- Wszystkie zależności przez interfejsy
- Łatwe testowanie i mockowanie
- Możliwość wymiany implementacji

### 3. **Single Responsibility**
- Każdy serwis ma jedną odpowiedzialność
- Małe, skupione klasy
- Łatwiejsze utrzymanie

## 🔧 Główne Komponenty

### Domain Services

#### `EventDomainService`
```php
- createEvent(EventDTO $eventDTO, User $organizer): Event
- updateEvent(Event $event, EventDTO $eventDTO): Event
- canUserModifyEvent(Event $event, User $user): bool
- isEventPublishable(Event $event): bool
```

#### `EventPublishingService`
```php
- publishEvent(Event $event, User $publisher): DateTimeImmutable
- canUserPublishEvent(Event $event, User $user): bool
- cancelEvent(Event $event, User $canceller, string $reason): void
```

### Application Services

#### `EventApplicationService`
```php
- createEvent(EventDTO $eventDTO, User $organizer): Event
- updateEvent(Event $event, EventDTO $eventDTO, User $user): Event
- publishEvent(Event $event, User $publisher): void
- getPublishedEvents(): array
- getEventById(string $eventId): ?Event
```

#### `NotificationApplicationService`
```php
- sendEventPublishedNotifications(Event $event): void
- sendEventCancelledNotifications(Event $event): void
- shareEventOnSocialMedia(Event $event): void
- sendNotificationToUser(string $userId, array $data): void
- sendGlobalNotification(array $data): void
```

### Infrastructure

#### `CacheInterface`
```php
- get(string $key, callable $callback, int $ttl): mixed
- set(string $key, mixed $value, int $ttl): bool
- delete(string $key): bool
- deletePattern(string $pattern): bool
- clear(): bool
- isEnabled(): bool
```

#### `MessageBusInterface`
```php
- publishEvent(array $eventData): bool
- publishNotification(array $data, ?string $userId): bool
- publishSocial(array $data, string $platform): bool
- publish(string $exchange, string $routingKey, array $data): bool
```

#### `EmailServiceInterface`
```php
- sendTicketConfirmation(Ticket $ticket): void
- sendEventPublishedNotification(Event $event, User $subscriber): void
- sendEventCancelledNotification(Event $event, User $ticketHolder): void
- sendEmail(string $to, string $subject, string $template, array $context): void
```

## 🔄 Migracja z Legacy

### Stare serwisy (do usunięcia):
- `src/Service/EventService.php` ❌
- `src/Service/NotificationService.php` ❌
- `src/Service/CacheService.php` ❌
- `src/Service/EmailService.php` ❌
- `src/Service/RabbitMQConnection.php` ❌

### Nowe serwisy:
- `src/Domain/Event/Service/*` ✅
- `src/Application/Service/*` ✅
- `src/Infrastructure/*` ✅

## 🧪 Testowanie

### Unit Tests
```php
// Domain Services - czysta logika biznesowa
EventDomainServiceTest
EventPublishingServiceTest

// Application Services - orchestracja
EventApplicationServiceTest
NotificationApplicationServiceTest
```

### Integration Tests
```php
// Infrastructure - testy z prawdziwymi zależnościami
RedisCacheAdapterTest
RabbitMQAdapterTest
SymfonyMailerAdapterTest
```

## 🚀 Korzyści

### 1. **Lepsze testowanie**
- Mocki przez interfejsy
- Izolowane unit testy
- Szybsze testy

### 2. **Łatwiejsze utrzymanie**
- Małe, skupione klasy
- Jasne odpowiedzialności
- Mniej coupling

### 3. **Większa elastyczność**
- Łatwa wymiana implementacji
- Dodawanie nowych funkcji
- Refactoring bez breaking changes

### 4. **Lepsza wydajność**
- Optymalizowany cache
- Efektywne messaging
- Asynchroniczne operacje

## 📋 TODO

1. ✅ Stworzenie nowej struktury
2. ✅ Implementacja Domain Services
3. ✅ Implementacja Application Services
4. ✅ Implementacja Infrastructure
5. ✅ Aktualizacja Message Handlers
6. ⏳ Testy jednostkowe
7. ⏳ Testy integracyjne
8. ⏳ Usunięcie legacy serwisów
9. ⏳ Dokumentacja API

## 🔧 Konfiguracja

### services.yaml
```yaml
# Infrastructure Services
App\Infrastructure\Cache\CacheInterface:
    alias: App\Infrastructure\Cache\RedisCacheAdapter

App\Infrastructure\Messaging\MessageBusInterface:
    alias: App\Infrastructure\Messaging\RabbitMQAdapter

App\Infrastructure\Email\EmailServiceInterface:
    alias: App\Infrastructure\Email\SymfonyMailerAdapter
```

### .env
```bash
REDIS_URL=redis://localhost:6379
FROM_EMAIL=noreply@eventhub.com
```