# 🚀 EventHub - Quick Start Guide

## One-Command Setup

Start everything locally with a single command:

```bash
./start-dev.sh up
```

This automatically:
- ✅ Starts all Docker services
- ✅ Installs dependencies (Composer + npm)
- ✅ Runs database migrations
- ✅ Starts PHP dev server (`http://127.0.0.1:8001`)
- ✅ Starts Vite dev server (`http://localhost:5173`)

## Available Commands

```bash
./start-dev.sh up       # Start environment ← Most common
./start-dev.sh down     # Stop all services
./start-dev.sh logs     # View live logs
./start-dev.sh status   # Check service status
./start-dev.sh reset    # Full reset (clears volumes)
```

## What Gets Started

| Service | URL | Purpose |
|---------|-----|---------|
| Frontend | http://localhost:5173 | React UI |
| Backend API | http://127.0.0.1:8001 | REST API |
| PostgreSQL | localhost:5433 | Database |
| Redis | localhost:6379 | Cache |
| RabbitMQ | localhost:5672 | Message queue |
| Mercure | http://localhost:3001 | Real-time updates |

## First Run Example

```bash
# 1. Navigate to project
cd /path/to/EventHub

# 2. Create dev branch (don't work on main!)
git checkout -b dev

# 3. Start everything
./start-dev.sh up

# 4. Open in browser
# - Frontend: http://localhost:5173
# - API: http://127.0.0.1:8001

# 5. When done, stop services
./start-dev.sh down
```

## Common Issues

### Port Already in Use
```bash
./start-dev.sh down    # Stop first
./start-dev.sh up      # Start again
```

### Docker Services Won't Start
```bash
# Check Docker status
docker-compose -f docker-compose.test.yml ps

# View logs
docker-compose -f docker-compose.test.yml logs
```

### Want to See Live Logs?
```bash
./start-dev.sh logs    # Shows backend + frontend logs
```

## Important Rules

⚠️ **NEVER:**
- Commit to `main` branch directly
- Push `.env.local` or `vendor/` or `node_modules/`
- Run production builds locally

✅ **ALWAYS:**
- Create feature branch: `git checkout -b feature/xyz`
- Test locally before committing
- Push to your feature branch first
- Create PR to `dev` branch (not `main`)

## Next Steps

1. Read [DEVELOPMENT.md](./DEVELOPMENT.md) for detailed guide
2. Check [README.md](./README.md) for full documentation
3. Read [REGISTRATION_FEATURES.md](./REGISTRATION_FEATURES.md) for requirements
4. Start coding!

## Still Stuck?

```bash
# View detailed logs
./start-dev.sh logs

# Check if services are running
./start-dev.sh status

# Reset and try again
./start-dev.sh reset
./start-dev.sh up
```

---

That's it! You're ready to develop. 🎉
