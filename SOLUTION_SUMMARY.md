# 🎯 Rozwiązanie problemu z wylogowywaniem po odświeżeniu strony

## 📋 Problem
Po odświeżeniu strony (F5) na produkcji użytkownik był natychmiast wylogowywany - `auth_token` znikał z localStorage.

## 🔍 Diagnoza

### Objawy:
1. Po zalogowaniu token był generowany poprawnie
2. Token był ważny (OpenSSL verification: ✅)
3. Po odświeżeniu strony `/api/auth/me` zwracał 401 Unauthorized
4. Frontend widział 401 i usuwał tokeny z localStorage
5. Header `Authorization: Bearer <token>` był wysyłany z przeglądarki

### Główna przyczyna:
**Apache nie przekazywał headera `Authorization` do PHP!**

Z logów backendu:
```
[JWT] supports() called - URI: /api/auth/me, Has Auth Header: NO, Auth Header: NULL, Has Prefix: NO
```

Mimo że przeglądarka wysyłała header `Authorization`, backend PHP go nie otrzymywał.

## ✅ Rozwiązanie

### Commit: `63e3f0c`
Dodano konfigurację Apache mod_rewrite w `backend/Dockerfile`:

```dockerfile
# Enable Authorization header pass-through for JWT authentication
RUN printf '<IfModule mod_rewrite.c>\\nRewriteEngine On\\nRewriteCond %%{HTTP:Authorization} ^(.*)\\nRewriteRule .* - [e=HTTP_AUTHORIZATION:%%1]\\n</IfModule>\\n' > /etc/apache2/conf-available/auth-header.conf \
    && a2enconf auth-header
```

Ta konfiguracja:
1. Używa `mod_rewrite` żeby przechwycić header `Authorization`
2. Ustawia zmienną środowiskową `HTTP_AUTHORIZATION`
3. Symfony/PHP może teraz odczytać ten header

## 🔧 Dodatkowe naprawy (wcześniejsze commity)

### 1. Frontend - AuthContext (`aed3cca`, `198ae63`)
- Tokeny są usuwane **tylko przy 401** (nie przy błędach sieciowych)
- Dodano retry mechanism dla przejściowych błędów
- Dodano szczegółowe logowanie

### 2. Frontend - API Client (`aed3cca`, `198ae63`)  
- Interceptor nie usuwa tokenów przy błędach sieciowych
- Tylko przy 401 z refresh endpoint

### 3. Backend - Konfiguracja (`569e36e`)
- Dodano `CORS_ALLOW_ORIGIN` do środowiska Docker Swarm
- Logger do JwtAuthenticator dla debugowania

## 📊 Timeline wdrożenia

1. **Commit aed3cca** - Naprawa logiki usuwania tokenów (frontend)
2. **Commit 198ae63** - Dodanie stack traces do debugowania
3. **Commit 569e36e** - Dodanie CORS_ALLOW_ORIGIN do backendu
4. **Commit 0dea849** - Dodanie logowania do JwtAuthenticator
5. **Commit 378732a** - Dodanie debug endpoint
6. **Commit 63e3f0c** - **GŁÓWNA NAPRAWA** - Authorization header pass-through

## 🧪 Testowanie po wdrożeniu

Po wdrożeniu commit `63e3f0c`:

### 1. Test z curl (z serwera):
```bash
TOKEN="<your_token>"
curl -H "Authorization: Bearer $TOKEN" https://eventuiapp.com/api/auth/me
```
**Powinno zwrócić:** 200 OK z danymi użytkownika

### 2. Test w przeglądarce:
1. Zaloguj się na https://eventuiapp.com
2. Odśwież stronę (F5)
3. **Powinno:** Pozostać zalogowanym
4. Sprawdź Console (F12) - powinien być log z sukcesu

### 3. Sprawdź logi backendu:
```bash
ssh mikoaj@docker-host 'docker service logs eventhub_backend --tail 50 | grep "[JWT]"'
```
**Powinno pokazać:** `Has Auth Header: YES`

## 📚 Wyjaśnienie technicze

### Dlaczego Apache blokuje header Authorization?

Apache domyślnie **nie przekazuje** headera `Authorization` do skryptów CGI/FastCGI ze względów bezpieczeństwa. Jest to zamierzone zachowanie opisane w dokumentacji Apache.

### Rozwiązanie z mod_rewrite

Używając mod_rewrite możemy:
1. Przechwycić header `Authorization` z requestu HTTP
2. Przekazać go jako zmienną środowiskową `HTTP_AUTHORIZATION`
3. PHP może odczytać tę zmienną przez `$_SERVER['HTTP_AUTHORIZATION']`

Symfony/Lexik JWT Bundle automatycznie sprawdza zarówno standardowy header jak i zmienną środowiskową.

## 🎉 Rezultat

Po wdrożeniu użytkownicy:
- ✅ Pozostają zalogowani po odświeżeniu strony
- ✅ Nie są wylogowywani przy przejściowych błędach sieciowych
- ✅ JWT authentication działa poprawnie
- ✅ Refresh token flow działa poprawnie

## 📝 Lessons Learned

1. **Apache + PHP + JWT wymaga specjalnej konfiguracji**
2. **Logi są kluczowe** - `error_log()` w PHP trafia do docker logs
3. **Testuj na wielu poziomach** - od przeglądarki przez Traefik do PHP
4. **curl z serwera** pozwala ominąć CORS i problemy przeglądarki
5. **OpenSSL verification** pomaga odizolować problem z kluczami od problemu z infrastrukturą

## 🔗 Linki

- GitHub Actions: https://github.com/mikoajp/EventHub/actions
- Commit głównej naprawy: https://github.com/mikoajp/EventHub/commit/63e3f0c
- Apache mod_rewrite docs: https://httpd.apache.org/docs/current/mod/mod_rewrite.html
