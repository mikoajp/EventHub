# 🎯 START HERE

## Welcome to EventHub Development! 👋

This is your entry point. Follow these steps to get started.

---

## 📋 Step 1: Read This First (1 min)

You're reading it! ✅

---

## 📘 Step 2: Choose Your Path

### 🏃 I'm in a Hurry (5 minutes)
Read: **[QUICK_START.md](./QUICK_START.md)**

Then run:
```bash
./start-dev.sh up
```

### 🚶 I Have Time (15 minutes)
Read: **[DEVELOPMENT.md](./DEVELOPMENT.md)**

Then run:
```bash
./start-dev.sh up
```

### 📚 I Want Full Details (30 minutes+)
Read: **[README.md](./README.md)**

Then read:
- **[DEVELOPMENT.md](./DEVELOPMENT.md)**
- **[REGISTRATION_FEATURES.md](./REGISTRATION_FEATURES.md)**

Then run:
```bash
./start-dev.sh up
```

---

## ✨ Step 3: Start Developing

```bash
# Navigate to project
cd /Users/mikoaj/Projects/EventHub

# Create your feature branch
git checkout -b feature/your-feature-name

# Start the environment
./start-dev.sh up

# Open in browser
# Frontend: http://localhost:5173
# API: http://127.0.0.1:8001
```

---

## 📚 Documentation Files

| File | Purpose | Time |
|------|---------|------|
| **[QUICK_START.md](./QUICK_START.md)** | Quick reference guide | 5 min ⚡ |
| **[DEVELOPMENT.md](./DEVELOPMENT.md)** | Detailed development guide | 15 min 📖 |
| **[SETUP_COMPLETE.md](./SETUP_COMPLETE.md)** | Setup summary | 5 min 📋 |
| **[README.md](./README.md)** | Full project documentation | 30 min 📚 |
| **[REGISTRATION_FEATURES.md](./REGISTRATION_FEATURES.md)** | Feature specifications | 20 min 📝 |

---

## 🔧 Main Script: start-dev.sh

This is your main tool for development:

```bash
./start-dev.sh up       # Start everything
./start-dev.sh down     # Stop everything
./start-dev.sh logs     # View live logs
./start-dev.sh status   # Check status
./start-dev.sh reset    # Hard reset
```

---

## ⚠️ Important Rules

### ❌ NEVER
- Push to `main` branch directly
- Commit `.env.local`, `vendor/`, `node_modules/`
- Make changes without a feature branch

### ✅ ALWAYS
- Create feature branch: `git checkout -b feature/xyz`
- Push to your feature branch first
- Test locally before committing
- Read documentation before asking questions

---

## 🆘 Quick Help

### "How do I start?"
→ Read [QUICK_START.md](./QUICK_START.md)

### "How do I develop features?"
→ Read [DEVELOPMENT.md](./DEVELOPMENT.md)

### "What's the full setup?"
→ Read [README.md](./README.md)

### "What features do I need to implement?"
→ Read [REGISTRATION_FEATURES.md](./REGISTRATION_FEATURES.md)

### "What do I do with start-dev.sh?"
→ Run: `./start-dev.sh` (shows help)

### "Services won't start?"
→ See [DEVELOPMENT.md](./DEVELOPMENT.md#troubleshooting) → Troubleshooting

### "Still stuck?"
→ Run: `./start-dev.sh logs`

---

## 🚀 30-Second Quick Start

```bash
cd /Users/mikoaj/Projects/EventHub
git checkout -b dev
./start-dev.sh up
```

Wait 2-3 minutes, then:
- Frontend: http://localhost:5173
- Backend: http://127.0.0.1:8001

---

## 📱 Services That Will Start

| Service | URL | Why |
|---------|-----|-----|
| Frontend | http://localhost:5173 | React app |
| API | http://127.0.0.1:8001 | PHP backend |
| Docs | http://127.0.0.1:8001/api/docs | API documentation |
| PostgreSQL | localhost:5433 | Database |
| Redis | localhost:6379 | Cache |
| RabbitMQ | localhost:5672 | Message queue |
| Mercure | http://localhost:3001 | Real-time updates |

---

## 🎓 Learning Path

1. **Immediate** (right now)
   - [ ] Read [QUICK_START.md](./QUICK_START.md)
   - [ ] Run `./start-dev.sh status`

2. **Today** (next hour)
   - [ ] Run `./start-dev.sh up`
   - [ ] Create feature branch
   - [ ] Open frontend/backend in browser

3. **This Week**
   - [ ] Read [DEVELOPMENT.md](./DEVELOPMENT.md)
   - [ ] Read [REGISTRATION_FEATURES.md](./REGISTRATION_FEATURES.md)
   - [ ] Make first commit
   - [ ] Create first PR to `dev` branch

4. **Later**
   - [ ] Read full [README.md](./README.md)
   - [ ] Understand project architecture
   - [ ] Implement features from requirements

---

## ✅ Checklist Before Your First Commit

- [ ] Created feature branch (`git checkout -b feature/xyz`)
- [ ] Made changes locally
- [ ] Tested changes (`./start-dev.sh up`)
- [ ] Read documentation
- [ ] Not committing `.env.local`, `vendor/`, `node_modules/`
- [ ] Commit message is clear

---

## 🎯 Ready?

### Next: Read [QUICK_START.md](./QUICK_START.md)

---

**Questions?** Check the docs, they have all the answers!

**Ready to start?** Run: `./start-dev.sh up` 🚀
