# CQRS: Ujednolicenie użycia i egzekwowanie zasad (Plan zmian)

Data: 2025-10-25
Zakres: backend/symfony (Messenger, kontrolery, Application Services, Handlery)
Cel: spójne egzekwowanie CQRS (Command/Query/Event) bez obchodzenia busów, jednoznaczne routowanie i ograniczenie logiki zapisu do handlerów.

## 1. Stan obecny (skrót)
- Konfiguracja Messenger jest poprawna (3 busy: command/query/event, routing: Command→async/HighPriority, Query→sync, Event→notifications).
- Struktura katalogów CQRS jest poprawna (Message/* i MessageHandler/*).
- Niespójności użycia:
  - Wiele endpointów i Application Services wykonuje zapisy/odczyty bezpośrednio (repo/ORM) zamiast dispatchować Command/Query.
  - QueryBus praktycznie nie jest wykorzystywany (zapytania idą bezpośrednio do repozytoriów/ApplicationService).
  - W handlerach wstrzykiwany jest ogólny MessageBusInterface bez jawnego powiązania z busami (ryzyko użycia domyślnego command.bus).

## 2. Cele zmian
- Zapisy wyłącznie przez Command + Handler (asynchronicznie/domknięte transakcje).
- Odczyty przez Query + Handler (synchronicznie).
- Handlery publikują zdarzenia wyłącznie przez event.bus.
- Eliminacja logiki domenowej/ORM z kontrolerów; kontrolery dispatchują Command/Query.
- Zmiany minimalne dla API (brak zmian kontraktu HTTP).

## 3. Kierunek architektoniczny (wariant rekomendowany „B” – zmiana inkrementalna)
- Kontrolery: dispatchują Command/Query przez właściwe busy.
- Application Services: krótkoterminowo mogą delegować Query do QueryBus (zachowując cache), a zapisy przepinamy na Command (stopniowo wygaszając bezpośrednie wywołania ORM).
- Długoterminowo: Application Services pełnią rolę orkiestracji i cross-cutting (cache, notyfikacje), ale bez mutacji stanu – te zostają w Handlerach.

Alternatywa „A” (bardziej radykalna): kontrolery dispatchują bezpośrednio, Application Services stają się tylko fasadą do Query (read side) – większa zmiana, nie rekomendowana na start.

## 4. Zmiany techniczne (konkretne)
1) Jawne wstrzykiwanie busów w miejscach użycia
- Wykorzystać atrybut Autowire, aby związać właściwy bus:

```php
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;

public function __construct(
  #[Autowire(service: 'messenger.bus.command')] private MessageBusInterface $commandBus,
  #[Autowire(service: 'messenger.bus.query')]   private MessageBusInterface $queryBus,
  #[Autowire(service: 'messenger.bus.event')]   private MessageBusInterface $eventBus,
) {}
```

- Zastosować w kontrolerach, handlerach publikujących eventy oraz w serwisach, które chwilowo będą delegować do busów.

2) Kontrolery – spójny dispatch
- Zapisy (create/update/cancel/publish/purchase): zamienić bezpośrednie wywołania ApplicationService/ORM na dispatch odpowiednich Command.
- Odczyty (np. statystyki, dostępność biletów): dispatch Query i zwrócić wynik.

Przykład (GET /events/{id}/statistics):
```php
$result = $this->queryBus->dispatch(new GetEventStatisticsQuery($id, $from, $to));
return $this->json($result->last(HandledStamp::class)->getResult());
```

3) Application Services – delegacja do QueryBus i emitowanie Command (okres przejściowy)
- Miejsca z cache (np. getEventStatistics, checkTicketAvailability):
  - pozostawić cache, ale źródło danych pobrać przez QueryBus zamiast repo.
- Miejsca wykonujące zapisy (create/update/cancel/purchase):
  - zastąpić bezpośrednie modyfikacje encji dispatchowaniem Command.

4) Handlery – właściwy bus do publikacji zdarzeń
- Zamiast ogólnego MessageBusInterface – jawne wstrzyknięcie event.bus (patrz pkt 1).
- Publikować Event* wyłącznie przez event.bus (routowany na „notifications”).

5) Konfiguracja i przewiązania
- messenger.yaml – pozostaje bez zmian (routing jest poprawny).
- (Opcjonalnie) services.yaml – można dodać aliasy ułatwiające wstrzykiwanie:
```yaml
services:
  App\Bus\CommandBus: '@messenger.bus.command'
  App\Bus\QueryBus:   '@messenger.bus.query'
  App\Bus\EventBus:   '@messenger.bus.event'
```
- Alternatywnie pozostać przy atrybucie Autowire (mniej zmian w DI).

## 5. Plan migracji (inkrementalny)
Faza 1 – Infrastruktura (0.5 dnia)
- Wprowadzić Autowire do dedykowanych busów w kontrolerach i handlerach publikujących eventy.
- Uporządkować PublishEventHandler/PurchaseTicketHandler, by używały event.bus do Event*.

Faza 2 – Queries (1 dzień)
- Dla: GetEventStatisticsQuery, CheckTicketAvailabilityQuery – przełączyć kontrolery (lub ApplicationService) na QueryBus.
- Zachować istniejący cache; źródło danych z QueryHandler.
- Dodać testy ścieżek read (min. smoke/e2e).

Faza 3 – Commands (1–2 dni)
- Create/Update/Cancel/Unpublish/Publish/Purchase – kontrolery przełączone na dispatch Command.
- Przenieść mutacje z ApplicationService do Handlerów (jeśli jeszcze zostały).
- Zapewnić transakcje przez middleware doctrine_transaction (już skonfigurowane).

Faza 4 – Porządki i twarde egzekwowanie (0.5 dnia)
- Lint/Deptrac (jeśli używany) – reguły zabraniające bezpośrednich zapisów z kontrolerów i read z write-side.
- Usunąć martwy kod/bezpośrednie wywołania repo w ApplicationService (po migracji).

## 6. Kryteria akceptacji
- 0 bezpośrednich zapisów (flush/persist) z kontrolerów – wszystkie zapisy przez Command Handler.
- Odczyty wymagające agregacji i logiki → przez Query + Handler; QueryBus jest używany w 100% takich ścieżek.
- Publikacja zdarzeń wyłącznie przez event.bus; brak użycia „gołego” MessageBusInterface bez Autowire.
- Brak regresji kontraktów API; integracje/monitoring działają (failed transport, retry).

## 7. Ryzyka i mitigacje
- Granice transakcji: polegać na doctrine_transaction middleware; dodać testy konkurencji w krytycznych miejscach.
- Idempotencja komend asynchronicznych: rozważyć deduplikację (message id) w krytycznych procesach (np. płatności).
- Walidacja: przenieść walidacje domenowe do Handlerów/Domain Services; walidacje wejścia pozostają w kontrolerach/validatorach.
- Wydajność zapytań: QueryHandler-y mogą korzystać z wyspecjalizowanych repo/DTO/projekcji; cache pozostaje.

## 8. Przykłady refaktoryzacji (fragmenty)
- Wstrzyknięcie właściwego busa w handlerze publikującym eventy:
```php
public function __construct(
  #[Autowire(service: 'messenger.bus.event')] private MessageBusInterface $eventBus,
) {}
```
- Kontroler (write):
```php
$this->commandBus->dispatch(new PublishEventCommand($id, $userId));
return $this->accepted();
```
- Kontroler (read):
```php
$envelope = $this->queryBus->dispatch(new GetEventStatisticsQuery($id, $from, $to));
$result = $envelope->last(\Symfony\Component\Messenger\Stamp\HandledStamp::class)->getResult();
return $this->json($result);
```

## 9. Testy i obserwowalność
- Testy funkcjonalne ścieżek Command/Query (min. dla krytycznych endpointów: publish, purchase, statistics, availability).
- Monitoring/alerting dla failed transport („failed” Doctrine) i metryk kolejki (RabbitMQ).

## 10. Plan wdrożenia i rollback
- Wdrażać fazami (F1→F4) z krótkimi PR-ami; każdy etap z testami i smoke na środowisku testowym.
- Rollback: cofnięcie PR-u; brak zmian w schemacie DB ułatwia powrót.

## 11. Lista zadań (checklista)
- [ ] Dodać Autowire busów w kontrolerach i handlerach publikujących eventy.
- [ ] Przełączyć read endpointy na QueryBus (statystyki, dostępność biletów).
- [ ] Przełączyć write endpointy na CommandBus (create/update/cancel/unpublish/publish/purchase).
- [ ] Usunąć bezpośrednie modyfikacje ORM z ApplicationService; pozostawić cache/orchestrację.
- [ ] Sprawdzić i poprawić publikację Event* na event.bus.
- [ ] Testy funkcjonalne + monitoring failed transport.
- [ ] Aktualizacja README (sekcja architektury/CQRS) po migracji.

---
Uwagi: Plan minimalizuje ryzyko regresji – najpierw zapewnia poprawne wstrzyknięcie busów i delegację Query, później przenosi zapisy na Command. API pozostaje bez zmian, a ścieżki async/sync są egzekwowane przez routing Messengera.
