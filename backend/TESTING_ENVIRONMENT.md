# 🧪 Testing Environment Setup Guide

## Overview

This guide explains how to set up and run different types of tests in the EventHub application.

---

## 📋 Table of Contents

1. [Quick Start](#quick-start)
2. [Test Types](#test-types)
3. [Environment Configuration](#environment-configuration)
4. [Docker Setup](#docker-setup)
5. [Running Tests](#running-tests)
6. [Writing Tests](#writing-tests)
7. [CI/CD Integration](#cicd-integration)
8. [Troubleshooting](#troubleshooting)

---

## 🚀 Quick Start

### Minimum Setup (Unit Tests Only)

```bash
# No additional setup needed!
cd backend
php bin/phpunit tests/Unit
```

### Full Setup (All Test Types)

```bash
# 1. Start Docker services
./scripts/docker-test-start.sh

# 2. Setup test environment
./scripts/test-setup.sh  # Linux/Mac
# OR
./scripts/test-setup.ps1  # Windows

# 3. Run all tests
cd backend
php bin/phpunit
```

---

## 🎯 Test Types

### 1. Unit Tests ✅ (152 tests)
- **Location:** `backend/tests/Unit/`
- **Requirements:** None (pure PHP)
- **Speed:** Very fast (~50ms)
- **Purpose:** Test individual components in isolation

### 2. Integration Tests
- **Location:** `backend/tests/Integration/`
- **Requirements:** Database, Cache
- **Speed:** Fast (~2-5s)
- **Purpose:** Test component interactions

### 3. Functional Tests
- **Location:** `backend/tests/Functional/`
- **Requirements:** Database, HTTP client
- **Speed:** Medium (~5-10s)
- **Purpose:** Test HTTP endpoints and API

### 4. E2E Tests (Future)
- **Location:** `backend/tests/E2E/` (to be created)
- **Requirements:** Full stack (database, frontend, backend)
- **Speed:** Slow (~30s+)
- **Purpose:** Test complete user workflows

---

## ⚙️ Environment Configuration

### Test Environment Files

#### `.env.test` (Main test config)
- Already configured for SQLite (no setup needed)
- Fast and isolated tests
- Good for unit and integration tests

#### `.env.test.local` (Your local overrides)
Create this file to override test settings:

```bash
# Copy example
cp backend/.env.test.local.example backend/.env.test.local

# Edit for your setup
# Use PostgreSQL from Docker:
DATABASE_URL="postgresql://test_user:test_pass@localhost:5433/eventhub_test?serverVersion=15&charset=utf8"

# Use Redis from Docker:
REDIS_URL=redis://localhost:6380/1
```

### Configuration Options

#### Database Options

**SQLite (Default - No setup needed):**
```bash
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_test.db"
```

**PostgreSQL (Docker required):**
```bash
DATABASE_URL="postgresql://test_user:test_pass@localhost:5433/eventhub_test"
```

**MySQL (Docker required):**
```bash
DATABASE_URL="mysql://test_user:test_pass@localhost:3307/eventhub_test"
```

#### Cache Options

**Array Cache (Default - No setup needed):**
```yaml
# Configured in config/packages/test/framework.yaml
cache:
  app: cache.adapter.array
```

**Redis (Docker required):**
```bash
REDIS_URL=redis://localhost:6380/1
```

---

## 🐳 Docker Setup

### Start Test Services

```bash
# Start all test containers
./scripts/docker-test-start.sh

# Or manually:
docker-compose -f docker-compose.test.yml up -d
```

### Available Services

| Service | Port | Container Name |
|---------|------|----------------|
| PostgreSQL | 5433 | eventhub_postgres_test |
| MySQL | 3307 | eventhub_mysql_test |
| Redis | 6380 | eventhub_redis_test |
| RabbitMQ | 5673 | eventhub_rabbitmq_test |
| RabbitMQ Management | 15673 | eventhub_rabbitmq_test |

### Stop Test Services

```bash
./scripts/docker-test-stop.sh

# Or manually:
docker-compose -f docker-compose.test.yml down
```

---

## 🏃 Running Tests

### All Unit Tests
```bash
cd backend
php bin/phpunit tests/Unit
```

### With Detailed Output
```bash
php bin/phpunit tests/Unit --testdox
```

### Specific Test File
```bash
php bin/phpunit tests/Unit/Domain/ValueObject/EmailTest.php
```

### Integration Tests
```bash
# Requires database setup
./scripts/test-setup.sh
php bin/phpunit tests/Integration
```

### Functional Tests
```bash
# Requires full environment
./scripts/test-setup.sh
php bin/phpunit tests/Functional
```

### All Tests
```bash
php bin/phpunit
```

### With Coverage (requires Xdebug)
```bash
php bin/phpunit --coverage-html coverage/
```

### Filter by Test Name
```bash
php bin/phpunit --filter testEmailValidation
```

### Stop on First Failure
```bash
php bin/phpunit --stop-on-failure
```

---

## ✍️ Writing Tests

### Unit Test Example

```php
<?php

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testValidEmail(): void
    {
        $email = Email::fromString('test@example.com');
        
        $this->assertSame('test@example.com', $email->toString());
    }
}
```

### Integration Test Example

```php
<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Tests\BaseTestCase;

final class EventRepositoryIntegrationTest extends BaseTestCase
{
    private EventRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = self::getContainer()->get(EventRepository::class);
    }

    public function testFindPublishedEvents(): void
    {
        // Arrange
        $event = new Event();
        $event->setName('Test Event');
        $event->setStatus(Event::STATUS_PUBLISHED);
        // ... set other required fields
        
        $this->persistAndFlush($event);
        
        // Act
        $result = $this->repository->findPublishedEvents();
        
        // Assert
        $this->assertCount(1, $result);
        $this->assertSame('Test Event', $result[0]->getName());
    }
}
```

### Functional Test Example

```php
<?php

namespace App\Tests\Functional\Api;

use App\Tests\BaseWebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class EventApiTest extends BaseWebTestCase
{
    public function testGetEvents(): void
    {
        // Act
        $response = $this->jsonRequest('GET', '/api/events');
        
        // Assert
        $data = $this->assertJsonResponse($response, Response::HTTP_OK);
        $this->assertIsArray($data);
    }

    public function testCreateEventRequiresAuthentication(): void
    {
        // Act
        $response = $this->jsonRequest('POST', '/api/events', [
            'name' => 'New Event'
        ]);
        
        // Assert
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }
}
```

### Using Test Fixtures

```php
use App\Tests\Fixtures\TestFixtures;

// In your test:
$user = $this->getReference(TestFixtures::USER_REFERENCE);
$event = $this->getReference(TestFixtures::EVENT_REFERENCE);
```

---

## 🔄 CI/CD Integration

### GitHub Actions

Tests are automatically run on every push and pull request.

```yaml
# .github/workflows/ci.yml
- name: Run PHPUnit (Unit Tests)
  run: cd backend && vendor/bin/phpunit tests/Unit --testdox
```

### Current CI Configuration

- ✅ **Unit tests** - Always run (no dependencies)
- ⚠️ **Integration tests** - Need environment setup
- ⚠️ **Functional tests** - Need environment setup

### Adding Integration Tests to CI

1. Add database service to GitHub Actions
2. Setup test environment in CI
3. Run integration tests

Example:
```yaml
services:
  postgres:
    image: postgres:15
    env:
      POSTGRES_DB: eventhub_test
      POSTGRES_USER: test_user
      POSTGRES_PASSWORD: test_pass
    ports:
      - 5432:5432

steps:
  - name: Setup test database
    run: ./scripts/test-setup.sh
    
  - name: Run integration tests
    run: php bin/phpunit tests/Integration
```

---

## 🔧 Troubleshooting

### "Cannot declare class App\Kernel"

**Problem:** Multiple Kernel instances loaded  
**Solution:** Run only unit tests or fix bootstrap in integration tests

```bash
# Instead of:
php bin/phpunit  # Runs all tests, may fail

# Use:
php bin/phpunit tests/Unit  # Unit tests only
```

### Database Connection Failed

**Problem:** Database not accessible  
**Solutions:**

1. Check Docker containers are running:
```bash
docker ps | grep eventhub
```

2. Verify port is not in use:
```bash
# Linux/Mac
lsof -i :5433

# Windows
netstat -ano | findstr :5433
```

3. Check connection string in `.env.test.local`

### "Table not found"

**Problem:** Database schema not created  
**Solution:** Run setup script

```bash
./scripts/test-setup.sh
```

### Tests are Slow

**Solutions:**

1. Use SQLite instead of PostgreSQL (faster)
2. Run only unit tests (no database)
3. Use `--stop-on-failure` flag
4. Run specific test files instead of all tests

### Cache Issues

**Problem:** Old test data persists  
**Solution:** Clear test cache

```bash
php bin/console cache:clear --env=test
```

---

## 📚 Best Practices

### 1. Test Isolation
- Each test should be independent
- Use transactions (BaseTestCase does this automatically)
- Clean up after tests

### 2. Test Data
- Use fixtures for complex setups
- Create test data in tests for simple cases
- Don't rely on specific database state

### 3. Test Speed
- Keep unit tests fast (< 100ms each)
- Use SQLite for most integration tests
- Use PostgreSQL only when testing DB-specific features

### 4. Test Organization
```
tests/
├── Unit/           # Fast, isolated tests
├── Integration/    # Database + services
├── Functional/     # HTTP/API tests
└── E2E/           # Full stack tests (future)
```

### 5. Naming Conventions
- Test class: `{ClassName}Test.php`
- Test method: `testMethodDescription()`
- Use descriptive names: `testEmailIsNormalizedToLowercase()`

---

## 🎯 Next Steps

1. ✅ Run unit tests (no setup needed)
2. 🔄 Setup Docker for integration tests
3. 🔄 Run full test suite
4. 📝 Write tests for your new features
5. 🚀 Add E2E tests (future)

---

## 📖 Additional Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Symfony Testing](https://symfony.com/doc/current/testing.html)
- [Test-Driven Development](https://en.wikipedia.org/wiki/Test-driven_development)

---

**Questions?** Check existing tests in `backend/tests/` for examples!
