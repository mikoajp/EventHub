# EventHub - Event Management Platform

![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php)
![Symfony](https://img.shields.io/badge/Symfony-7.2-000000?style=flat&logo=symfony)
![React](https://img.shields.io/badge/React-19.1-61DAFB?style=flat&logo=react)
![TypeScript](https://img.shields.io/badge/TypeScript-5.8-3178C6?style=flat&logo=typescript)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=flat&logo=postgresql)
![Redis](https://img.shields.io/badge/Redis-7-DC382D?style=flat&logo=redis)

A modern, scalable event management platform built with **Symfony 7.2** (backend) and **React 19** (frontend), following **Domain-Driven Design (DDD)** and **CQRS** patterns.

---

## 📋 Table of Contents

- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Architecture](#-architecture)
- [Prerequisites](#-prerequisites)
- [Quick Start](#-quick-start)
- [Development](#-development)
- [API Documentation](#-api-documentation)
- [Testing](#-testing)
- [Project Structure](#-project-structure)
- [Documentation](#-documentation)
- [Contributing](#-contributing)
- [License](#-license)

---

## ✨ Features

### Core Features
- 🎫 **Event Management** - Create, publish, and manage events
- 🎟️ **Ticket System** - Multiple ticket types, reservations, purchases
- 💳 **Payment Processing** - Stripe integration for secure payments
- 👥 **User Management** - JWT authentication, role-based access control
- 📊 **Analytics** - Event statistics, sales tracking, performance metrics
- 🔔 **Real-time Notifications** - Mercure for live updates
- 📧 **Email Notifications** - Event publishing, ticket confirmations

### Technical Features
- ⚡ **High Performance** - Redis caching, optimized queries
- 🔒 **Security** - JWT tokens, CSRF protection, rate limiting
- 🏗️ **Clean Architecture** - DDD, CQRS, Event Sourcing patterns
- 📨 **Async Processing** - RabbitMQ message queue
- 🌐 **RESTful API** - API Platform with OpenAPI docs
- 🔄 **Real-time Updates** - Mercure SSE protocol
- 🐳 **Containerized** - Full Docker setup

---

## 🛠 Tech Stack

### Backend
- **Framework:** Symfony 7.2
- **PHP:** 8.2+
- **Database:** PostgreSQL 16
- **Cache:** Redis 7
- **Message Queue:** RabbitMQ 3
- **API:** API Platform 4.1
- **Auth:** JWT (Lexik JWT Bundle)
- **Real-time:** Mercure Hub

### Frontend
- **Framework:** React 19.1
- **Language:** TypeScript 5.8
- **UI Library:** Mantine 8.0
- **State Management:** Zustand, TanStack Query
- **Routing:** React Router v7
- **Forms:** React Hook Form + Zod
- **Build Tool:** Vite 6.3

### DevOps
- **Containerization:** Docker, Docker Compose
- **Testing:** PHPUnit, Doctrine Test Bundle
- **Code Quality:** PHPStan, Rector, ESLint
- **Architecture:** Deptrac (dependency analysis)

---

## 🏛️ Architecture

EventHub follows **Domain-Driven Design (DDD)** with clear separation of concerns:

```
backend/src/
├── Domain/           # Business logic, entities, domain services
├── Application/      # Use cases, application services, DTOs
├── Infrastructure/   # External services (cache, email, payment)
├── Presentation/     # Controllers, API resources, presenters
├── Message/          # Commands, queries, events (CQRS)
└── MessageHandler/   # Command/query/event handlers
```

### Key Patterns
- **DDD (Domain-Driven Design)** - Clear domain boundaries
- **CQRS** - Command/Query separation
- **Event Sourcing** - Domain events for async processing
- **Repository Pattern** - Data access abstraction
- **Presenter Pattern** - Output formatting separation

---

## 📦 Prerequisites

- **Docker** 24.0+ and **Docker Compose** 2.0+
- **Git** 2.0+
- (Optional) **Make** for convenience commands

---

## 🚀 Quick Start

### 1. Clone the Repository

```bash
git clone <repository-url>
cd eventhub
```

### 2. Start Services

```bash
docker-compose up -d
```

This starts:
- **Backend API** - http://localhost:8001
- **Frontend** - http://localhost:5173
- **PostgreSQL** - localhost:5432
- **Redis** - localhost:6379
- **RabbitMQ Management** - http://localhost:15672
- **Mercure Hub** - http://localhost:3000
- **MailHog** - http://localhost:8025

### 3. Install Dependencies

```bash
# Backend
docker-compose exec backend composer install

# Frontend (if not auto-installed)
docker-compose exec frontend npm install
```

### 4. Setup Database

```bash
# Create database
docker-compose exec backend php bin/console doctrine:database:create

# Run migrations
docker-compose exec backend php bin/console doctrine:migrations:migrate --no-interaction

# Load fixtures (optional - sample data)
docker-compose exec backend php bin/console doctrine:fixtures:load --no-interaction
```

### 5. Generate JWT Keys

```bash
docker-compose exec backend php bin/console lexik:jwt:generate-keypair
```

### 6. Access the Application

- **Frontend:** http://localhost:5173
- **API Docs:** http://localhost:8001/api/docs
- **Health Check:** http://localhost:8001/api/health

---

## 💻 Development

### Backend Development

#### Run PHP Console Commands

```bash
docker-compose exec backend php bin/console <command>
```

#### Common Commands

```bash
# Clear cache
docker-compose exec backend php bin/console cache:clear

# Create new migration
docker-compose exec backend php bin/console make:migration

# Run tests
docker-compose exec backend php bin/phpunit

# Check code quality
docker-compose exec backend vendor/bin/phpstan analyse

# Run Rector refactoring
docker-compose exec backend vendor/bin/rector process --dry-run
```

#### Consume Messages (Background Workers)

```bash
# Start consuming messages
docker-compose exec backend php bin/console messenger:consume async -vv

# With auto-restart on failure
docker-compose exec backend php bin/console messenger:consume async --time-limit=3600 --memory-limit=128M
```

### Frontend Development

#### NPM Commands

```bash
# Development server (auto-starts with docker-compose)
docker-compose exec frontend npm run dev

# Build for production
docker-compose exec frontend npm run build

# Lint code
docker-compose exec frontend npm run lint

# Type check
docker-compose exec frontend npm run build
```

### Database Management

```bash
# Access PostgreSQL CLI
docker-compose exec postgres psql -U eventhub -d eventhub

# Create database backup
docker-compose exec postgres pg_dump -U eventhub eventhub > backup.sql

# Restore database
docker-compose exec -T postgres psql -U eventhub eventhub < backup.sql
```

### Redis Management

```bash
# Access Redis CLI
docker-compose exec redis redis-cli

# Clear all cache
docker-compose exec redis redis-cli FLUSHALL

# Monitor Redis operations
docker-compose exec redis redis-cli MONITOR
```

### RabbitMQ Management

Access **RabbitMQ Management UI:** http://localhost:15672
- Username: `eventhub`
- Password: `secret`

---

## 📖 API Documentation

### Interactive API Documentation

Once the application is running, visit:

- **Swagger UI:** http://localhost:8001/api/docs
- **OpenAPI Spec:** http://localhost:8001/api/docs.json

### Key Endpoints

#### Authentication
```http
POST /api/auth/register        # Register new user
POST /api/auth/login           # Login (get JWT token)
POST /api/auth/refresh         # Refresh JWT token
```

#### Events
```http
GET    /api/events             # List all events (with filters)
POST   /api/events             # Create new event
GET    /api/events/{id}        # Get event details
PUT    /api/events/{id}        # Update event
DELETE /api/events/{id}        # Delete event
POST   /api/events/{id}/publish    # Publish event
POST   /api/events/{id}/cancel     # Cancel event
```

#### Tickets
```http
GET    /api/tickets            # List user's tickets
POST   /api/tickets/purchase   # Purchase tickets
GET    /api/tickets/{id}       # Get ticket details
POST   /api/tickets/{id}/cancel    # Cancel ticket
```

#### Admin
```http
DELETE /api/admin/cache        # Clear application cache
GET    /api/admin/cache/stats  # Get cache statistics
```

### Authentication

All protected endpoints require JWT token in header:

```http
Authorization: Bearer <your_jwt_token>
```

---

## 🧪 Testing

### Backend Tests

```bash
# Run all tests
docker-compose exec backend php bin/phpunit

# Run specific test suite
docker-compose exec backend php bin/phpunit --testsuite=unit

# Run with coverage
docker-compose exec backend php bin/phpunit --coverage-html coverage/
```

### Frontend Tests

```bash
# Run tests (when configured)
docker-compose exec frontend npm test

# Type checking
docker-compose exec frontend npm run build
```

### Code Quality

```bash
# PHPStan static analysis
docker-compose exec backend vendor/bin/phpstan analyse

# Deptrac architecture validation
docker-compose exec backend vendor/bin/deptrac analyse

# Rector automated refactoring
docker-compose exec backend vendor/bin/rector process
```

---

## 📁 Project Structure

```
eventhub/
├── backend/                      # Symfony backend
│   ├── bin/                      # Console, PHPUnit
│   ├── config/                   # Configuration files
│   │   ├── packages/             # Bundle configs
│   │   └── routes/               # Route definitions
│   ├── migrations/               # Database migrations
│   ├── public/                   # Public assets, index.php
│   ├── src/
│   │   ├── Application/          # Application layer (use cases)
│   │   │   ├── Command/          # Command handlers
│   │   │   ├── Query/            # Query handlers
│   │   │   └── Service/          # Application services
│   │   ├── Controller/           # HTTP controllers
│   │   │   ├── Api/              # API endpoints
│   │   │   └── Admin/            # Admin endpoints
│   │   ├── Domain/               # Domain layer (business logic)
│   │   │   ├── Event/            # Event aggregate
│   │   │   ├── Ticket/           # Ticket aggregate
│   │   │   ├── User/             # User aggregate
│   │   │   ├── Payment/          # Payment domain
│   │   │   └── Analytics/        # Analytics domain
│   │   ├── DTO/                  # Data Transfer Objects
│   │   ├── Entity/               # Doctrine entities
│   │   ├── Infrastructure/       # Infrastructure layer
│   │   │   ├── Cache/            # Cache implementations
│   │   │   ├── Email/            # Email service
│   │   │   ├── Payment/          # Payment gateway
│   │   │   └── Validation/       # Request validation
│   │   ├── Message/              # CQRS messages
│   │   │   ├── Command/          # Command messages
│   │   │   ├── Query/            # Query messages
│   │   │   └── Event/            # Domain events
│   │   ├── MessageHandler/       # Message handlers
│   │   ├── Presenter/            # Output presenters
│   │   ├── Repository/           # Data repositories
│   │   ├── Security/             # Auth, voters
│   │   └── State/                # API Platform state processors
│   ├── tests/                    # PHPUnit tests
│   ├── composer.json             # PHP dependencies
│   └── phpunit.xml.dist          # PHPUnit config
│
├── frontend/                     # React frontend
│   ├── public/                   # Static assets
│   ├── src/
│   │   ├── api/                  # API client
│   │   ├── components/           # React components
│   │   ├── contexts/             # React contexts
│   │   ├── hooks/                # Custom hooks
│   │   ├── pages/                # Page components
│   │   ├── types/                # TypeScript types
│   │   └── utils/                # Utilities
│   ├── package.json              # NPM dependencies
│   └── vite.config.ts            # Vite config
│
├── docker/                       # Docker configs
│   ├── nginx/                    # Nginx config
│   └── rabbitmq/                 # RabbitMQ config
│
├── docs/                         # Documentation
│   ├── adr/                      # Architecture Decision Records
│   └── audit/                    # Code audit reports
│
├── docker-compose.yml            # Docker Compose setup
├── deptrac.yaml                  # Architecture rules
├── phpstan.neon                  # PHPStan config
├── rector.php                    # Rector config
├── README.md                     # This file
│
└── Analysis Documents:           # Project analysis
    ├── ANALIZA_DUPLIKACJA.md     # Code duplication analysis
    ├── ANALIZA_NIEUZYWANE.md     # Unused code analysis
    ├── ANALIZA_PROJEKTU.md       # Project overview
    ├── PLAN_DDD_SZCZEGOLOWY.md   # DDD implementation plan
    └── REKOMENDACJE_PRIORYTETOWE.md  # Priority recommendations
```

---

## 📚 Documentation

### Architecture Decision Records (ADR)
- [ADR-001: Separation of Presentation from Domain](docs/adr/ADR-001-separation-of-presentation-from-domain.md)

### Analysis Documents
- [Project Analysis](ANALIZA_PROJEKTU.md) - Complete project overview
- [Unused Code Analysis](ANALIZA_NIEUZYWANE_FINAL.md) - Comprehensive unused code report
- [Code Duplication](ANALIZA_DUPLIKACJA.md) - Duplication analysis
- [DDD Implementation Plan](PLAN_DDD_SZCZEGOLOWY.md) - Domain-Driven Design strategy
- [Priority Recommendations](REKOMENDACJE_PRIORYTETOWE.md) - Actionable improvements

### Code Audit
- [Audit Reports](docs/audit/) - Security and quality audits

---

## 🔧 Configuration

### Environment Variables

Copy `.env` and configure:

```bash
# Backend
cp backend/.env backend/.env.local
```

Key variables:
```env
APP_ENV=dev
APP_SECRET=your-secret-key
DATABASE_URL=postgresql://eventhub:secret@postgres:5432/eventhub
MESSENGER_TRANSPORT_DSN=amqp://eventhub:secret@rabbitmq:5672/%2f/messages
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your-passphrase
MERCURE_URL=http://mercure/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost:3000/.well-known/mercure
MERCURE_JWT_SECRET=!ChangeThisMercureHubJWTSecretKey!
```

### Frontend Configuration

```bash
# Frontend
cp frontend/.env.example frontend/.env
```

Key variables:
```env
VITE_API_URL=http://localhost:8001
VITE_MERCURE_URL=http://localhost:3000/.well-known/mercure
```

---

## 🐛 Troubleshooting

### Common Issues

**1. Port already in use**
```bash
# Check what's using the port
lsof -i :8001

# Stop conflicting service or change port in docker-compose.yml
```

**2. Database connection failed**
```bash
# Restart PostgreSQL
docker-compose restart postgres

# Check logs
docker-compose logs postgres
```

**3. JWT keys missing**
```bash
# Regenerate JWT keys
docker-compose exec backend php bin/console lexik:jwt:generate-keypair --overwrite
```

**4. Cache issues**
```bash
# Clear all caches
docker-compose exec backend php bin/console cache:clear
docker-compose exec redis redis-cli FLUSHALL
```

**5. RabbitMQ connection issues**
```bash
# Restart RabbitMQ
docker-compose restart rabbitmq

# Check management UI: http://localhost:15672
```

---

## 🚀 Deployment

### Production Build

```bash
# Backend
docker-compose exec backend composer install --no-dev --optimize-autoloader
docker-compose exec backend php bin/console cache:warmup --env=prod

# Frontend
docker-compose exec frontend npm run build
```

### Production Checklist
- [ ] Set `APP_ENV=prod` in backend
- [ ] Generate strong `APP_SECRET`
- [ ] Configure production database
- [ ] Setup SSL certificates
- [ ] Configure CORS for production domains
- [ ] Setup backup strategy
- [ ] Configure monitoring (Sentry, New Relic, etc.)
- [ ] Setup log aggregation
- [ ] Configure rate limiting
- [ ] Review security settings

---

## 🤝 Contributing

### Development Workflow

1. Create a feature branch
```bash
git checkout -b feature/your-feature-name
```

2. Make your changes following coding standards

3. Run tests and quality checks
```bash
docker-compose exec backend php bin/phpunit
docker-compose exec backend vendor/bin/phpstan analyse
docker-compose exec frontend npm run lint
```

4. Commit with conventional commits
```bash
git commit -m "feat: add user profile page"
```

5. Push and create Pull Request

### Coding Standards

- **PHP:** PSR-12, follow Symfony best practices
- **TypeScript:** Use ESLint config, prefer functional components
- **Git:** Conventional Commits (feat, fix, docs, style, refactor, test, chore)
- **Architecture:** Follow DDD principles, maintain layer boundaries

---

## 📊 Performance

### Optimization Features

- ✅ Redis caching for events, tickets, user data
- ✅ Database query optimization with indexes
- ✅ API pagination and filtering
- ✅ Async message processing with RabbitMQ
- ✅ Frontend code splitting and lazy loading
- ✅ CDN-ready static assets
- ✅ Response compression

### Monitoring

```bash
# Cache hit rate
docker-compose exec backend php bin/console cache:pool:list

# RabbitMQ queue status
# Visit: http://localhost:15672/#/queues

# Database performance
docker-compose exec postgres psql -U eventhub -d eventhub -c "SELECT * FROM pg_stat_statements;"
```

---

## 📝 License

This project is proprietary and confidential.

---

## 👥 Team

For questions or support, contact the development team.

---

## 🎯 Roadmap

### Completed ✅
- User authentication & authorization
- Event CRUD operations
- Ticket purchase system
- Payment integration (Stripe)
- Real-time notifications
- Admin dashboard
- Analytics tracking

### In Progress 🚧
- Payment refund system
- Ticket transfer functionality
- Advanced event statistics
- Mobile app (React Native)

### Planned 📋
- Multi-language support
- Social media integration
- Event recommendations
- Advanced reporting
- Waitlist management
- QR code scanning app

---

## 📞 Support

- **Documentation:** See `/docs` folder
- **Issues:** Use project issue tracker
- **API Docs:** http://localhost:8001/api/docs

---

**Built with ❤️ using Symfony, React, and modern DevOps practices**
