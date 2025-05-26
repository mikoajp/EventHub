# 🎫 EventHub - Event Management Platform

Modern event management platform with **Symfony 7** backend and **React 18** frontend. Create events, sell tickets, and manage everything in real-time.

## ✨ Features

- **🎯 Event Management** - Create, publish, and manage events
- **🎟️ Ticket Sales** - Multiple ticket types with inventory tracking
- **👥 User Roles** - Admin, Organizer, User with proper permissions
- **🔐 JWT Authentication** - Secure login and API access
- **⚡ Real-time Updates** - Live notifications via Mercure
- **📊 Dashboard** - Analytics and sales tracking

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+, Node.js 18+, Docker

### 1. Clone & Start Services
```bash
git clone https://github.com/your-username/eventhub.git
cd eventhub
docker-compose up -d
```

### 2. Backend Setup
```bash
cd backend
composer install
cp .env .env.local

# Generate JWT keys
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout

# Database & data
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load

# Start server
symfony server:start
```

### 3. Frontend Setup
```bash
cd frontend
npm install
npm run dev
```

**🌐 Access:** http://localhost:5173

## 👥 Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| **Admin** | admin@example.com | password |
| **Organizer** | organizer@example.com | password |
| **User** | user@example.com | password |

## 📁 Project Structure

```
EventHub/
├── backend/              # Symfony 7 API
│   ├── src/Controller/   # API endpoints
│   ├── src/Entity/       # Database models
│   ├── src/Message/      # CQRS commands/queries
│   └── config/           # Configuration
├── frontend/             # React 18 + TypeScript
│   ├── src/components/   # UI components
│   ├── src/pages/        # Page components
│   ├── src/contexts/     # React contexts
│   └── src/api/          # API client
└── docker-compose.yml    # Services (DB, Redis, etc.)
```

## 🔧 Key Configuration

### Backend (.env.local)
```bash
DATABASE_URL="postgresql://eventhub:secret@localhost:5432/eventhub"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=eventhub
MESSENGER_TRANSPORT_DSN=amqp://eventhub:secret@localhost:5672/%2f/messages
MERCURE_URL=http://localhost:3000/.well-known/mercure
```

### Frontend (.env)
```bash
VITE_API_BASE_URL=http://localhost:8000
```

## 🌐 Main API Endpoints

```bash
# Authentication
POST /api/auth/login     # Login
GET  /api/auth/me        # Current user

# Events
GET  /api/events         # List events
POST /api/events         # Create event (Organizer)
POST /api/events/{id}/publish  # Publish event

# Tickets
POST /api/tickets/purchase     # Buy tickets
GET  /api/tickets/my          # My tickets
```

## ⚡ Development Commands

```bash
# Backend
php bin/console cache:clear          # Clear cache
php bin/console doctrine:fixtures:load  # Reset test data
php bin/console messenger:consume async # Start workers
tail -f var/log/dev.log             # View logs

# Frontend
npm run dev          # Development server
npm run build        # Production build
npm run type-check   # TypeScript validation
```

## 🔍 Troubleshooting

**CORS Issues:**
```bash
composer require nelmio/cors-bundle
```

**JWT Problems:**
```bash
# Regenerate keys
openssl genpkey -out config/jwt/private.pem -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

**Database Issues:**
```bash
docker-compose restart postgres
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

**Can't Login:**
```bash
# Test manually
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

## 🎯 Tech Stack

- **Backend:** Symfony 7, Doctrine ORM, JWT Auth, Mercure
- **Frontend:** React 18, TypeScript, Mantine UI, React Query
- **Database:** PostgreSQL, Redis
- **Queue:** RabbitMQ, Symfony Messenger
- **Real-time:** Mercure Hub (WebSockets)

## 📄 License

MIT License - see [LICENSE](LICENSE) file.

---

**Ready to manage events like a pro! 🚀**
