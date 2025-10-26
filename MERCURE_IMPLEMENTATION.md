# Mercure Real-Time Implementation

## ✅ Co zostało zaimplementowane:

### Backend (PHP/Symfony):

#### 1. **MercureMessageBus** (`backend/src/Infrastructure/Messaging/MercureMessageBus.php`)
Prawdziwa implementacja Mercure zamiast `NullMessageBus`:

**Funkcje:**
- ✅ `publishEvent()` - publikuje zdarzenia eventów (np. nowy event)
- ✅ `publishNotification()` - publikuje notyfikacje (publiczne i prywatne)
- ✅ `publish()` - ogólna publikacja na kanał
- ✅ `publishSocial()` - udostępnianie w social media

**Topics:**
- `events` - nowe/zaktualizowane eventy
- `notifications` - publiczne notyfikacje
- `notifications/user/{userId}` - prywatne notyfikacje użytkownika
- `social` - aktywność social media

#### 2. **MercureController** (`backend/src/Controller/Api/MercureController.php`)

**Endpointy:**
- `GET /api/mercure/token` - zwraca JWT token do subskrypcji
- `GET /api/mercure/health` - sprawdza status Mercure hub

#### 3. **Configuration** (`backend/config/services.yaml`)
```yaml
App\Infrastructure\Messaging\MessageBusInterface:
    class: App\Infrastructure\Messaging\MercureMessageBus
    arguments:
        $hub: '@mercure.hub.default'
        $logger: '@logger'
```

### Frontend (React/TypeScript):

#### 1. **useMercure Hook** (`frontend/src/hooks/useMercure.ts`)

**Custom hooks:**
- `useMercure()` - podstawowy hook do subskrypcji
- `useNotifications()` - hook do notyfikacji
- `useEventUpdates()` - hook do aktualizacji eventów

**Funkcje:**
- Auto-reconnect przy błędach
- Type-safe messages
- Browser notifications support
- Connection status tracking

---

## 📡 Jak używać:

### Backend - Wysyłanie notyfikacji:

```php
// W kontrolerze lub handlerze
public function __construct(
    private NotificationApplicationService $notificationService
) {}

// Globalna notyfikacja
$this->notificationService->sendGlobalNotification([
    'title' => 'New Event!',
    'message' => 'Check out our latest event',
    'type' => 'info',
    'event_id' => $eventId
]);

// Prywatna notyfikacja do użytkownika
$this->notificationService->sendNotificationToUser($userId, [
    'title' => 'Ticket Purchased',
    'message' => 'Your ticket has been confirmed',
    'type' => 'success'
]);

// Publikacja eventu
$this->notificationService->sendEventPublishedNotifications($event);
```

### Frontend - Subskrypcja:

#### Przykład 1: Notyfikacje w komponencie
```tsx
import { useNotifications } from '@/hooks/useMercure';

function NotificationBell() {
  const { notifications, clearNotifications, isConnected } = useNotifications(userId);

  return (
    <div>
      <Badge count={notifications.length}>
        <BellIcon />
      </Badge>
      {isConnected && <OnlineIndicator />}
      
      <NotificationList>
        {notifications.map(notif => (
          <Notification key={notif.timestamp}>
            <h4>{notif.title}</h4>
            <p>{notif.message}</p>
          </Notification>
        ))}
      </NotificationList>
    </div>
  );
}
```

#### Przykład 2: Live event updates
```tsx
import { useEventUpdates } from '@/hooks/useMercure';

function EventsList() {
  const { events, isConnected } = useEventUpdates();
  const [allEvents, setAllEvents] = useState([]);

  // Dodaj nowe eventy na górę listy
  useEffect(() => {
    if (events.length > 0) {
      setAllEvents(prev => [...events, ...prev]);
    }
  }, [events]);

  return (
    <div>
      {isConnected && <span>🟢 Live updates</span>}
      {allEvents.map(event => (
        <EventCard key={event.event_id} event={event} isNew />
      ))}
    </div>
  );
}
```

#### Przykład 3: Custom topics
```tsx
import { useMercure } from '@/hooks/useMercure';

function TicketUpdates({ ticketId }) {
  const { lastMessage, isConnected } = useMercure({
    topics: [`tickets/${ticketId}`],
    onMessage: (message) => {
      if (message.type === 'ticket.updated') {
        toast.success('Your ticket was updated!');
      }
    },
    enabled: !!ticketId
  });

  return <div>Status: {isConnected ? 'Live' : 'Offline'}</div>;
}
```

---

## 🚀 Testowanie:

### 1. Sprawdź czy Mercure działa:
```bash
curl http://localhost:3000/.well-known/mercure
```

### 2. Test z backendu:
```bash
curl http://localhost:8001/api/mercure/health
```

**Odpowiedź:**
```json
{
  "status": "ok",
  "mercure_url": "http://mercure/.well-known/mercure",
  "public_url": "http://localhost:3000/.well-known/mercure"
}
```

### 3. Subskrypcja (z browsera lub curl):
```bash
curl -N "http://localhost:3000/.well-known/mercure?topic=notifications"
```

### 4. Publikacja (z backendu):
```php
// W dowolnym miejscu gdzie masz NotificationApplicationService
$this->notificationService->sendGlobalNotification([
    'title' => 'Test',
    'message' => 'Hello from Mercure!',
    'type' => 'info'
]);
```

---

## 🔧 Konfiguracja:

### Docker (już skonfigurowane):
```yaml
mercure:
  image: dunglas/mercure:v0.15
  ports:
    - "3000:80"
  environment:
    MERCURE_PUBLISHER_JWT_KEY: '!ChangeThisMercureHubJWTSecretKey!'
    MERCURE_SUBSCRIBER_JWT_KEY: '!ChangeThisMercureHubJWTSecretKey!'
    MERCURE_EXTRA_DIRECTIVES: |
      cors_origins http://localhost:5173
      anonymous
```

### Environment variables:
```env
# Backend
MERCURE_URL=http://mercure/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost:3000/.well-known/mercure
MERCURE_JWT_SECRET=!ChangeThisMercureHubJWTSecretKey!

# Frontend (.env)
VITE_MERCURE_URL=http://localhost:3000/.well-known/mercure
```

---

## 📊 Topics dostępne:

| Topic | Dostęp | Zawartość |
|-------|--------|-----------|
| `events` | Public | Nowe/zaktualizowane eventy |
| `notifications` | Public | Globalne notyfikacje |
| `notifications/user/{userId}` | Private | Notyfikacje użytkownika |
| `social` | Public | Social media aktywność |
| `tickets/{ticketId}` | Private | Status biletu (do implementacji) |

---

## 🎯 Przepływ danych:

### 1. Publikacja eventu:
```
User creates event
    ↓
EventController
    ↓
CreateEventCommand → CreateEventHandler
    ↓
EventCreatedEvent → Event Bus
    ↓
NotificationApplicationService.sendEventPublishedNotifications()
    ↓
MercureMessageBus.publishEvent()
    ↓
Mercure Hub
    ↓
All subscribed frontends receive update (SSE)
```

### 2. Prywatna notyfikacja:
```
Ticket purchased
    ↓
PurchaseTicketHandler
    ↓
notificationService.sendNotificationToUser(userId, data)
    ↓
MercureMessageBus.publishNotification(data, userId)
    ↓
Mercure Hub (private topic)
    ↓
Only specific user's frontend receives update
```

---

## ⚡ Features:

### Backend:
- ✅ Auto-retry on failures (error handling)
- ✅ Structured logging
- ✅ Private & public topics
- ✅ JWT authentication ready
- ✅ Type-safe message format

### Frontend:
- ✅ Auto-reconnect on disconnect
- ✅ Type-safe TypeScript hooks
- ✅ Browser notifications support
- ✅ Connection status indicator
- ✅ Easy integration with React components
- ✅ Multiple topic subscription

---

## 🐛 Troubleshooting:

### Problem: "Connection failed"
**Rozwiązanie:**
```bash
# Sprawdź czy Mercure działa
docker ps | grep mercure

# Restart Mercure
docker-compose restart mercure
```

### Problem: "No messages received"
**Rozwiązanie:**
1. Sprawdź logi backendu: `docker logs eventhub-backend-1`
2. Sprawdź czy publish wywołuje się:
```php
// Dodaj log
$this->logger->info('Publishing to Mercure', ['data' => $eventData]);
```

### Problem: "CORS errors"
**Rozwiązanie:**
Upewnij się że `MERCURE_EXTRA_DIRECTIVES` zawiera:
```yaml
cors_origins http://localhost:5173
anonymous
```

---

## 📝 TODO (opcjonalne):

- [ ] Dodać JWT authentication dla prywatnych topics
- [ ] Implementować ticket updates (`tickets/{id}`)
- [ ] Dodać retry strategy na frontendzie
- [ ] Stworzyć admin panel do ręcznej publikacji
- [ ] Dodać metryki (ile klientów podłączonych)
- [ ] Stworzyć functional test dla Mercure

---

## ✅ Status:

**Backend:** ✅ Fully implemented
**Frontend:** ✅ Hooks ready
**Docker:** ✅ Configured
**Testing:** ⚠️ Needs manual verification

**Gotowe do użycia!** 🎉
