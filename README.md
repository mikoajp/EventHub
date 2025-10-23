# EventHub


EventHub is a modern event management platform that enables organizers to create and manage events while providing users with a seamless experience to discover and purchase tickets.

## ✨ Features

- **Event Management**: Create, edit, and manage events with rich details
- **Ticket System**: Configure multiple ticket types with different pricing options
- **User Roles**: Admin, Organizer, and User access levels
- **Real-time Updates**: Instant notifications for event changes and ticket purchases
- **Secure Payments**: Integrated payment processing (placeholder)
- **Email Notifications**: Automated emails for registrations and ticket purchases

## 🚀 Technologies

### Backend
- **Symfony 7**: PHP framework for building the API
- **Doctrine ORM**: Object-relational mapping for database interactions
- **API Platform**: RESTful API creation
- **JWT Authentication**: Secure user authentication
- **CQRS Pattern**: Command Query Responsibility Segregation for business logic
- **PostgreSQL**: Relational database
- **RabbitMQ**: Message queue for asynchronous processing
- **Mercure**: Real-time updates

### Frontend
- **React 18**: UI library
- **TypeScript**: Type-safe JavaScript
- **React Router**: Client-side routing
- **React Query**: Data fetching and caching
- **Context API**: State management
- **Tailwind CSS**: Utility-first CSS framework

## 🔑 Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| **Admin** | admin@example.com | password |
| **Organizer** | organizer@example.com | password |
| **User** | user@example.com | password |

## 📁 Project Structure

```
EventHub/
├── backend/                    # Symfony 7 API
│   ├── src/Controller/         # API endpoints
│   ├── src/Entity/             # Database models
│   ├── src/Message/            # CQRS commands/queries
│   └── config/                 # Configuration
├── frontend/                   # React 18 + TypeScript
│   ├── src/components/         # UI components
│   ├── src/pages/              # Page components
│   ├── src/contexts/           # React contexts
│   └── src/api/                # API client
└── docker-compose.yml          # Services (DB, Redis, etc.)
```

## 🔧 Installation

### Prerequisites

- Docker and Docker Compose
- PHP 8.2+
- Composer
- Node.js 18+
- npm or yarn

### Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/mikoajp/EventHub.git
   cd EventHub
   ```

2. **Start Docker services**
   ```bash
   docker-compose up -d
   ```

3. **Set up backend**
   ```bash
   cd backend
   composer install
   
   # Generate JWT keys (will prompt for passphrase)
   php bin/console lexik:jwt:generate-keypair
   
   # Set up secrets for development
   php bin/console secrets:generate-keys --local
   php bin/console secrets:set APP_SECRET --local
   php bin/console secrets:set JWT_PASSPHRASE --local
   php bin/console secrets:set STRIPE_SECRET_KEY --local
   
   # Database setup
   php bin/console doctrine:migrations:migrate
   php bin/console doctrine:fixtures:load
   
   # Start server
   symfony serve -d
   ```

4. **Set up frontend**
   ```bash
   cd frontend
   npm install
   npm start
   ```

5. **Access the application**
    - Frontend: http://localhost:3000
    - API: http://localhost:8000/api

## 📝 API Documentation

API documentation is available at http://localhost:8000/api/docs when the backend server is running.

## 🧪 Testing

### Backend
```bash
cd backend
php bin/phpunit
```

### Frontend
```bash
cd frontend
npm test
```

## ✅ TODO (2025-10-22)
- ✅ ~~Secrets: move JWT/APP/Stripe to Symfony Secrets; remove from .env and README.~~
- Auth/Security: json_login + JWT issuance, refresh tokens, tighten access_control, API Platform security/voters.
- Messaging: standardize on Symfony Messenger + RabbitMQ (php-amqplib or amqp ext); fix DSN/hosts; drop unused STOMP/Mercure or wire it fully.
- Redis: replace KEYS usage with SCAN/tagging in cache code.
- Healthcheck: keep a single endpoint (controller or public/health.php) and remove duplicate.
- Dev/Ports/Proxy: align docker-compose ports (8000/8001) with server/Apache; fix Vite proxy and VITE_API_URL; update README.
- Frontend API: use import.meta.env.VITE_API_URL; implement JWT + refresh handling.
- DDD/Refactor: move presentation logic out of entities to DTO/Presenters; retire legacy src\Service.
- Payments: finalize Stripe integration and webhook flow; move keys to secrets; add tests.
- CI/Tests: add minimal CI, smoke tests (auth/events/messenger) and critical e2e.

## 📜 License

This project is licensed under the MIT License - see the **LICENSE** file for details.