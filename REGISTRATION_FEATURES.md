# EventHub - User Registration & Profile Features

## ✅ Co zostało zaimplementowane

### 1. **Rejestracja Użytkowników**
- **Unified Login/Register Form** - Formularz z dwoma zakładkami (Sign In / Sign Up)
- **Walidacja danych**:
  - Email validation (RFC 5322)
  - Hasło min. 6 znaków
  - Confirmacja hasła
  - Imię i nazwisko min. 2 znaki
- **Integracja z AuthContext** - `register()` method z pełnym obsługą błędów
- **Route**: `/login` (zawiera obie opcje)

### 2. **Strona Profilu Użytkownika**
- **Route**: `/profile` (chroniona, tylko zalogowani użytkownicy)
- **Informacje profilu**:
  - Avatar z inicjałami
  - Wyświetlanie imienia, nazwiska, emailu
  - Rola użytkownika (Admin/Organizer/User) z kolorowymi badge'ami
- **Statystyki konta**:
  - Status konta (Active)
  - Weryfikacja emailu (Yes)
  - Data dołączenia (Member Since)
- **Zakładki ustawień** (Settings Tabs):
  - **Edit Profile** - Placeholder dla przyszłej edycji (imię, nazwisko, email, telefon)
  - **Change Password** - Placeholder dla przyszłej zmiany hasła
- **Danger Zone** - Placeholder dla usunięcia konta

### 3. **Integracja UI**
- **Header Menu** - Link do Profile w menu użytkownika
- **Nawigacja** - Profile dostępny z menu dropdown w headerze
- **AuthContext** - Nowa metoda `register()` z obsługą rejestracji

---

## 📋 API Endpoints (Backend - już istniejące)

```
POST /api/auth/register
{
  "email": "user@example.com",
  "password": "password123",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+48123456789" (optional)
}

Response (HTTP 201):
{
  "token": "eyJ0eXAi...",
  "refresh_token": "abcd1234...",
  "user": {
    "id": "uuid",
    "email": "user@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "roles": ["ROLE_USER"],
    "createdAt": "2025-12-01T22:30:00Z"
  }
}
```

---

## 🗂️ Struktura Plików

```
frontend/src/
├── pages/
│   ├── LoginPage.tsx (zmodyfikowany - dodane rejestracja)
│   ├── ProfilePage.tsx (nowy - profil użytkownika)
│   └── RegisterPage.tsx (nowy - standalone register, jeśli potrzebny)
├── contexts/
│   └── AuthContext.tsx (zmodyfikowany - dodana metoda register)
└── components/
    └── Layout.tsx (zmodyfikowany - link do profilu)
```

---

## 🚀 Jak Zacząć

### 1. **Uruchomić aplikację lokalnie**
```bash
cd /Users/mikoaj/Projects/EventHub
./start-dev.sh up
```

### 2. **Przejść na stronę logowania**
```
http://localhost:5173/login
```

### 3. **Rejestracja nowego użytkownika**
- Kliknij na zakładkę "Sign Up"
- Wypełnij formularz:
  - Imię (min 2 znaki)
  - Nazwisko (min 2 znaki)
  - Email (prawidłowy format)
  - Hasło (min 6 znaków)
  - Potwierdź hasło
- Kliknij "Create Account"
- Automatyczne logowanie i redirect na stronę główną

### 4. **Dostęp do profilu**
- Kliknij na Avatar/Menu w prawym górnym rogu
- Kliknij "Profile"
- Wyświetlone zostaną informacje o koncie

---

## 🧪 Testowe Konta

```
Admin: admin@example.com / password
User:  user@example.com / password
```

---

## 📝 Przyszłe Ulepszenia

- [ ] **Edit Profile** - API endpoint do aktualizacji danych profilu
- [ ] **Change Password** - API endpoint do zmiany hasła
- [ ] **Delete Account** - API endpoint do usunięcia konta
- [ ] **Email Verification** - Wysyłanie linku weryfikacyjnego
- [ ] **Avatar Upload** - Możliwość uploadu customowego avatara
- [ ] **Two-Factor Authentication** - 2FA dla większego bezpieczeństwa
- [ ] **Account Activity Log** - Historia logowań i zmian
- [ ] **Notification Preferences** - Ustawienia powiadomień

---

## 🔒 Bezpieczeństwo

✅ **Wdrożone**:
- Walidacja na frontend i backend
- Password hashing (bcrypt)
- JWT authentication
- Refresh tokens (7-dniowy TTL)
- CORS protection
- HttpOnly cookies dla refresh tokens

⚠️ **Rekomendacje**:
- Implementacja rate limiting na `/auth/register`
- Email verification before account activation
- CAPTCHA dla zapobiegania botom
- Password strength meter w formularzu

---

## 📞 Kontakt/Wsparcie

W razie pytań, sprawdź:
1. Browser console (DevTools) - błędy JavaScript
2. Backend logs: `tail -f backend/var/log/dev.log`
3. Network tab - sprawdzenie request/response API

---

**Ostatnia aktualizacja**: 2025-12-01 22:35:00 UTC
