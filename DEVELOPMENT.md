# EventHub Development Guide

## Quick Start

Start the entire development environment with a single command:

```bash
./start-dev.sh up
```

This will:
- ✅ Start all Docker services (PostgreSQL, MySQL, Redis, RabbitMQ, Mercure)
- ✅ Install backend dependencies (Composer)
- ✅ Run database migrations
- ✅ Install frontend dependencies (npm)
- ✅ Start PHP development server on `http://127.0.0.1:8001`
- ✅ Start Vite dev server on `http://localhost:5173`

## Available Commands

### Start Environment
```bash
./start-dev.sh up
```
Starts all services and keeps them running. Press `Ctrl+C` to stop.

### Stop Environment
```bash
./start-dev.sh down
```
Stops all services gracefully.

### View Logs
```bash
./start-dev.sh logs
```
Shows combined logs from backend and frontend.

### Check Status
```bash
./start-dev.sh status
```
Shows running status of all services.

### Reset Everything
```bash
./start-dev.sh reset
```
⚠️ **Careful!** This will:
- Stop all services
- Remove Docker volumes (database data)
- Remove `vendor/` and `node_modules/`

## Service URLs

Once started, services are available at:

| Service | URL | Notes |
|---------|-----|-------|
| Frontend | http://localhost:5173 | Vite dev server |
| Backend API | http://127.0.0.1:8001 | PHP dev server |
| Mercure | http://localhost:3001 | Real-time updates |
| RabbitMQ Admin | http://localhost:15673 | User: guest, Pass: guest |
| PostgreSQL | localhost:5433 | DB: eventhub_test |
| MySQL | localhost:3306 | DB: eventhub_test |
| Redis | localhost:6379 | Cache/queue |

## Test Credentials

```
Email:    admin@example.com
Password: Use the password from your database or re-run fixtures
```

## Manual Service Management

### Start Docker Services Only
```bash
docker-compose -f docker-compose.test.yml up -d
```

### Stop Docker Services
```bash
docker-compose -f docker-compose.test.yml down
```

### View Docker Logs
```bash
docker-compose -f docker-compose.test.yml logs -f [service-name]
```

### Backend Only

Install dependencies:
```bash
cd backend
composer install
```

Run migrations:
```bash
php bin/console doctrine:migrations:migrate
```

Start dev server:
```bash
php -S 127.0.0.1:8001 -t public
```

### Frontend Only

Install dependencies:
```bash
cd frontend
npm install
```

Start dev server:
```bash
npm run dev
```

## Troubleshooting

### Port Already in Use
If you get "port already in use" errors:

```bash
# Kill processes on specific ports
lsof -ti:8001 | xargs kill -9  # Backend
lsof -ti:5173 | xargs kill -9  # Frontend
lsof -ti:5433 | xargs kill -9  # PostgreSQL
```

### Database Connection Issues
```bash
# Check if database is ready
docker-compose -f docker-compose.test.yml logs postgres_test

# Manually run migrations
cd backend
php bin/console doctrine:migrations:migrate --no-interaction
```

### Redis/RabbitMQ Connection Issues
```bash
# Verify services are running
docker-compose -f docker-compose.test.yml ps

# Restart specific service
docker-compose -f docker-compose.test.yml restart redis_test
```

### Frontend Build Issues
```bash
cd frontend
rm -rf node_modules package-lock.json
npm install
npm run dev
```

## Git Workflow

### Never Commit Configuration Files
These are **local only** and should not be committed:
- `.env.local` (both backend and frontend)
- `.backend.pid`
- `.frontend.pid`
- `vendor/`
- `node_modules/`
- `*-dev.log`

### Create a Development Branch
```bash
git checkout -b dev  # Create and switch to dev branch
./start-dev.sh up    # Start development
```

Make changes, then:
```bash
git add .
git commit -m "your message"
git push -u origin dev
```

⚠️ **Important**: Don't push to `main` directly! Always use a branch like `dev` for development changes.

## Environment Variables

### Backend (.env.local)
```env
APP_ENV=dev
APP_DEBUG=1
DATABASE_URL=postgresql://test_user:test_pass@127.0.0.1:5433/eventhub_test
REDIS_URL=redis://127.0.0.1:6379
RABBITMQ_HOST=127.0.0.1
RABBITMQ_PORT=5672
MERCURE_URL=http://127.0.0.1:3001/.well-known/mercure
```

### Frontend (.env)
```env
VITE_API_URL=http://localhost:8001/api
VITE_MERCURE_URL=http://localhost:3000/.well-known/mercure
```

## Performance Tips

1. **Use Composer cache**: Dependencies are cached between runs
2. **Use npm cache**: npm automatically caches downloads
3. **Docker resource limits**: Ensure Docker has enough memory (at least 4GB)
4. **Use `.env.local`**: Local environment overrides for faster builds

## Common Issues & Solutions

### Issue: `JWT Token not found` at `/create-event`
**Solution**: User must be authenticated. Check that token is being sent:
```javascript
// Frontend should send token with requests
// Check localStorage for auth_token
console.log(localStorage.getItem('auth_token'));
```

### Issue: `Access to XMLHttpRequest...CORS policy`
**Solution**: Backend CORS headers not set. Check:
```bash
cd backend
php bin/console debug:config nelmio_cors
```

### Issue: Mercure connection refused
**Solution**: Mercure service not running:
```bash
docker-compose -f docker-compose.test.yml logs mercure_test
```

### Issue: `composer require` hangs
**Solution**: PHP memory limit or network issue:
```bash
COMPOSER_MEMORY_LIMIT=-1 composer install
```

## Next Steps

1. Read `/REGISTRATION_FEATURES.md` for feature requirements
2. Check issue tracker for current tasks
3. Use `dev` branch for all development
4. Make atomic commits with clear messages
5. Test before committing changes

## Need Help?

Check logs for error details:
```bash
tail -f backend-dev.log
tail -f frontend-dev.log
./start-dev.sh logs
```

Document any issues you find!
