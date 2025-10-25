# 🚀 Testing Quick Start Guide

## TL;DR - Start Testing Now!

```bash
# 1. Run unit tests (no setup needed!) ✅
cd backend
php bin/phpunit tests/Unit

# 2. For integration/functional tests:
./scripts/test-setup.sh        # Linux/Mac
./scripts/test-setup.ps1        # Windows

# 3. (Optional) Start Docker services
./scripts/docker-test-start.sh  # Linux/Mac
docker-compose -f docker-compose.test.yml up -d  # Windows
```

---

## 📊 Current Test Status

- ✅ **152 Unit Tests** - Ready to run (no setup needed!)
- 🔧 **Integration Tests** - Require database setup
- 🔧 **Functional Tests** - Require full environment
- 📝 **E2E Tests** - Coming soon

---

## 🎯 Three Ways to Test

### 1️⃣ Quick Mode (Unit Tests Only)
**No setup needed! Works immediately!**

```bash
cd backend
php bin/phpunit tests/Unit
```

**What you get:**
- ✅ 152 tests
- ✅ ~50ms execution
- ✅ No dependencies
- ✅ Perfect for TDD

---

### 2️⃣ Standard Mode (Unit + Integration)
**Requires: SQLite (included in PHP)**

```bash
# Setup once
cd backend
./scripts/test-setup.sh  # or .ps1 for Windows

# Run tests
php bin/phpunit tests/Unit
php bin/phpunit tests/Integration
```

**What you get:**
- ✅ Unit tests (152)
- ✅ Integration tests (database queries)
- ✅ Fast execution
- ✅ SQLite - no Docker needed

---

### 3️⃣ Full Mode (All Tests)
**Requires: Docker**

```bash
# 1. Start services (once)
./scripts/docker-test-start.sh

# 2. Setup environment (once)
./scripts/test-setup.sh

# 3. Run all tests
cd backend
php bin/phpunit
```

**What you get:**
- ✅ Unit tests
- ✅ Integration tests (PostgreSQL/MySQL)
- ✅ Functional tests (HTTP/API)
- ✅ Full environment testing

---

## 🐳 Docker Services (Optional)

Start when needed:
```bash
./scripts/docker-test-start.sh
```

Available services:
- **PostgreSQL** - Port 5433
- **Redis** - Port 6380
- **RabbitMQ** - Port 5673
- **RabbitMQ UI** - http://localhost:15673

Stop when done:
```bash
./scripts/docker-test-stop.sh
```

---

## 📁 Test Structure

```
backend/tests/
├── Unit/              ✅ 152 tests ready
│   ├── Domain/       # Business logic
│   ├── Entity/       # Entities
│   ├── DTO/          # Data transfer objects
│   └── ...
├── Integration/      🔧 Needs setup
│   ├── Repository/   # Database tests
│   ├── Cache/        # Redis tests
│   └── ...
└── Functional/       🔧 Needs setup
    └── Api/          # HTTP endpoint tests
```

---

## 💡 Common Commands

```bash
# Run specific test file
php bin/phpunit tests/Unit/Domain/ValueObject/EmailTest.php

# Run with detailed output
php bin/phpunit tests/Unit --testdox

# Stop on first failure
php bin/phpunit --stop-on-failure

# Filter by name
php bin/phpunit --filter testEmailValidation

# Coverage report (requires Xdebug)
php bin/phpunit --coverage-html coverage/
```

---

## ✅ What's Working Now

### Unit Tests (152 tests)
- ✅ Value Objects (Email, Money, EventDate)
- ✅ Domain Services (Events, Tickets, Payments)
- ✅ Entities (User, Event, Ticket, Order)
- ✅ DTOs and Messages
- ✅ Infrastructure interfaces

### Test Infrastructure
- ✅ BaseTestCase for integration tests
- ✅ BaseWebTestCase for API tests
- ✅ Test fixtures
- ✅ Docker compose for services
- ✅ Setup scripts (Linux/Windows)
- ✅ Environment configuration

---

## 🔧 Troubleshooting

### "Cannot declare class App\Kernel"
**Solution:** Run only unit tests
```bash
php bin/phpunit tests/Unit
```

### Database errors
**Solution:** Run setup script
```bash
./scripts/test-setup.sh
```

### Docker services not working
**Solution:** Check containers
```bash
docker ps | grep eventhub
./scripts/docker-test-start.sh
```

---

## 📚 Full Documentation

For detailed information, see:
- `backend/TESTING_ENVIRONMENT.md` - Complete setup guide
- `backend/tests/README.md` - Test documentation
- `backend/FINAL_TEST_REPORT.md` - Test results

---

## 🎯 Next Steps

1. ✅ **Start now:** `php bin/phpunit tests/Unit`
2. 📖 Read: `backend/TESTING_ENVIRONMENT.md`
3. 🔧 Setup: `./scripts/test-setup.sh`
4. 🐳 Docker: `./scripts/docker-test-start.sh`
5. ✍️ Write your own tests!

---

**Happy Testing! 🧪**
