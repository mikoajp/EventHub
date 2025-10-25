#!/bin/bash
# Stop Docker containers for testing

set -e

echo "🛑 Stopping test Docker containers..."

# Change to project root
cd "$(dirname "$0")/.." || exit 1

# Stop test services
docker-compose -f docker-compose.test.yml down

echo "✅ Test services stopped!"
