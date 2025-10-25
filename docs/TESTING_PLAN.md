# Plan testów dla EventHub

Data: 2025-10-25T18:41:01.796Z

## Cel
Zapewnić stabilność i regresyjne bezpieczeństwo kluczowych obszarów: płatności, sprzedaż biletów, autoryzacja, kolejki/Messenger, zapytania (CQRS) i API.

## Rodzaje testów
- Testy jednostkowe (PHPUnit): logika domenowa/usługi, polityki/strategię, walidatory, mapery/presentery, DTO (niezmienność), helpery.
- Testy integracyjne: repozytoria Doctrine (SQLite in-memory + DAMA test bundle), konfiguracja DI, Messenger z InMemoryTransport, transakcje.
- Testy funkcjonalne/API: kontrolery, trasy, serializacja, walidacje, autoryzacja (JWT/refresh), ścieżki happy/edge, statusy HTTP.
- Testy kontraktowe: zgodność payloadów (JSON/OpenAPI) dla webhooków/klientów zewnętrznych; fakes dla Stripe/AMQP.
- Testy mutacyjne (opcjonalnie): Infection dla krytycznej logiki (płatności/wyliczenia dostępności biletów).
- Analiza statyczna: phpstan (docelowo lvl 7-8), coding style (php-cs-fixer).

## Priorytetowe scenariusze
1) Płatności
- PaymentService: poprawna delegacja do PaymentGateway, propagacja błędów, idempotencja (jeśli dotyczy), waluta/kwota/metadata.
- StripePaymentGateway (jeśli jest): mapowanie błędów, timeouts, retry policy (jeśli jest), interpretacja statusów.
- Webhooki: walidacja sygnatur, idempotencja, aktualizacja statusu zamówienia.

2) Sprzedaż biletów / Eventy
- EventCalculationService: wyliczenia dostępności, rabatów/opłat serwisowych, skrajne przypadki (0, max, brak miejsc).
- Repositories: getTicketSalesStatistics(), paginacja list, filtry (EventFiltersDTO), sortowanie, N+1 zapytania (asertywnie liczba zapytań opcjonalnie).
- Komendy/Zapytania (CQRS): poprawne mapowania do DTO, emisja zdarzeń/wiadomości.

3) Użytkownicy i autoryzacja
- Rejestracja/logowanie/refresh: walidacje, blokady (rate limiter), role, stany błędów (401/403/422).
- UserTickets: dostęp do swoich biletów vs brak dostępu do cudzych.

4) Messenger / Kolejki
- InMemoryTransport: dispatch/handle komend/zdarzeń, retriable błędy, obsługa dead-letter (jeśli jest), serializacja wiadomości.

5) DTO / Presentery
- Readonly DTO: konstrukcja, serializacja do JSON, brak magicznego kopiowania właściwości, zgodność pól z kontraktami API.

## Organizacja katalogów
- tests\Unit\...: usługi domenowe, polityki, walidatory, mapery, DTO.
- tests\Integration\...: repozytoria Doctrine, Messenger, konfiguracja DI.
- tests\Functional\...: WebTestCase/ApiTestCase dla endpointów (Auth, Events list/details, Purchase, Refund).
- tests\Contract\...: kontrakty JSON/OpenAPI, webhooki/klienci.
- tests\E2E\... (opcjonalnie): krytyczne ścieżki użytkownika end-to-end bez zewnętrznych usług.

## Narzędzia i środowisko
- PHPUnit 9: php -d variables_order=EGPCS vendor/bin/phpunit -c phpunit.xml.dist
- DB: SQLite in-memory + DAMA\DoctrineTestBundle.
- Messenger: InMemoryTransport dla testów.
- Fixtures: DoctrineFixturesBundle/Foundry do danych testowych.
- phpstan: config w backend\phpstan.neon, podnoszenie levelu stopniowo.
- php-cs-fixer: reguły PSR-12.

## Pokrycie i metryki
- Minimalne pokrycie: 70% globalnie, 90% dla PaymentService/EventCalculationService.
- Mutacje (Infection) dla metod krytycznych (obliczenia/finanse) – próg 60% MSI na start.

## Plan wdrożenia testów (etapy)
1) Szkielety testów jednostkowych dla PaymentService, EventCalculationService, walidatorów, mapperów (szybkie zyski).
2) Integracja: testy repozytoriów (kwerendy statystyczne, filtry), Messenger (handlers, dispatch), konfiguracja DI.
3) API: scenariusze Auth + Events (lista/szczegóły/kupno/zwrot), asercje schematów JSON.
4) Kontrakty i webhooki: fixture’y payloadów, weryfikacja podpisów.
5) Mutacje i podnoszenie phpstan level.

## Uwagi do CI
- Uruchamiaj: composer install, phpstan, phpunit (z wyciszeniem Xdebug), opcjonalnie infection w nightly.
- Stubbing: odłącz ext-amqp w CI lub erzac przez transport=sync w testach.
- Sekrety (.env.test): konfiguracja kluczy wymaganych przez DI (np. MERCURE_JWT_SECRET) tylko dla środowiska testowego.
