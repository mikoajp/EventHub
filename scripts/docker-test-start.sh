#!/bin/bash
# Start Docker containers for testing

set -e

echo "🐳 Starting test Docker containers..."

# Change to project root
cd "$(dirname "$0")/.." || exit 1

# Start test services
docker-compose -f docker-compose.test.yml up -d

echo "⏳ Waiting for services to be ready..."

# Wait for PostgreSQL
echo "Waiting for PostgreSQL..."
timeout 30 bash -c 'until docker exec eventhub_postgres_test pg_isready -U test_user 2>/dev/null; do sleep 1; done'

# Wait for Redis
echo "Waiting for Redis..."
timeout 30 bash -c 'until docker exec eventhub_redis_test redis-cli ping 2>/dev/null; do sleep 1; done'

echo "✅ All test services are ready!"
echo ""
echo "Services available:"
echo "  PostgreSQL: localhost:5433"
echo "  Redis: localhost:6380"
echo "  RabbitMQ: localhost:5673 (Management: http://localhost:15673)"
echo ""
echo "To stop services: docker-compose -f docker-compose.test.yml down"
