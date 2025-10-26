# Custom Exceptions Implementation Guide

## Nowe wyjątki wprowadzone do systemu

### 1. Idempotency (Idempotencja)
- **CommandAlreadyProcessingException** - komenda jest już przetwarzana
- **DuplicateRequestException** - wykryto duplikat żądania

### 2. Value Objects
- **InvalidCurrencyException** - nieprawidłowa waluta
- **InvalidMoneyAmountException** - nieprawidłowa kwota

### 3. Order (Zamówienia)
- **OrderNotFoundException** - zamówienie nie znalezione

## Zastąpione wyjątki

| Stary wyjątek | Nowy wyjątek | Lokalizacja |
|---------------|--------------|-------------|
| `\InvalidArgumentException` | `InvalidCurrencyException` | Money ValueObject |
| `\InvalidArgumentException` | `InvalidEmailException` | Email ValueObject |
| `\DomainException` | `InvalidPaymentAmountException` | PaymentDomainService |
| `\DomainException` | `UnsupportedCurrencyException` | PaymentDomainService |
| `\RuntimeException` | `UserNotAuthenticatedException` | AuthController |
| `\RuntimeException` | `CommandAlreadyProcessingException` | IdempotencyService |
| `\InvalidArgumentException` | Różne custom exceptions | Wszystkie Handlery |

## Struktura wyjątków

```
Exception
├── ApplicationException
│   ├── IdempotencyException
│   │   ├── CommandAlreadyProcessingException
│   │   └── DuplicateRequestException
│   └── ValidationException
│       ├── InvalidRequestDataException
│       ├── InvalidJsonException
│       └── InvalidEmailException
└── DomainException
    ├── ValueObjectException
    │   ├── InvalidCurrencyException
    │   └── InvalidMoneyAmountException
    ├── EventException
    ├── PaymentException
    ├── TicketException
    ├── UserException
    └── OrderException
```

## Przykłady użycia

### Przed:
```php
if ($currency === '') {
    throw new \InvalidArgumentException('Currency required');
}
```

### Po:
```php
if ($currency === '') {
    throw new InvalidCurrencyException($currency);
}
```

## Korzyści

1. ✅ **Lepsza czytelność** - natychmiast wiadomo, jaki typ błędu wystąpił
2. ✅ **Łatwiejsze debugowanie** - specyficzne komunikaty błędów
3. ✅ **Spójne kody HTTP** - każdy wyjątek ma odpowiedni kod statusu
4. ✅ **Type Safety** - IDE lepiej podpowiada typy
5. ✅ **Domain-Driven Design** - wyjątki odpowiadają strukturze domeny

## Statystyki

- **Nowe wyjątki utworzone**: 9
- **Plików zaktualizowanych**: 30+
- **Testów zaktualizowanych**: 10+
- **Testy jednostkowe**: ✅ 248 passed, 0 failed

## Migracja zakończona pomyślnie! ✨
