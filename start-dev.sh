#!/usr/bin/env bash
set -euo pipefail

CMD=${1:-up}
ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
COMPOSE_FILE="$ROOT/docker-compose.test.yml"
[[ -f "$COMPOSE_FILE" ]] || COMPOSE_FILE="$ROOT/docker-compose.yml"

# Dev env
export APP_ENV=dev
export APP_DEBUG=1
export DATABASE_URL="postgresql://test_user:test_pass@localhost:5433/eventhub_test"
export REDIS_URL="redis://localhost:6380"
export MESSENGER_TRANSPORT_DSN="amqp://test_user:test_pass@localhost:5673/%2f/messages"
export MERCURE_PUBLIC_URL=${MERCURE_PUBLIC_URL:-http://localhost:3001/.well-known/mercure}
export MERCURE_URL="$MERCURE_PUBLIC_URL"

php_up() {
  (cd "$ROOT/backend" && \
    composer install --no-interaction && \
    php bin/console doctrine:database:create --if-not-exists || true && \
    php bin/console doctrine:migrations:migrate -n && \
    php bin/console doctrine:fixtures:load -n || true && \
    php -S 127.0.0.1:8001 -t public > "$ROOT/backend-dev.log" 2>&1 & echo $! > "$ROOT/.php-dev.pid")
  echo "Backend running at http://127.0.0.1:8001"
}

php_down() {
  if [[ -f "$ROOT/.php-dev.pid" ]]; then
    kill "$(cat "$ROOT/.php-dev.pid")" || true
    rm -f "$ROOT/.php-dev.pid"
  fi
}

case "$CMD" in
  up)
    docker compose -f "$COMPOSE_FILE" up -d
    php_up
    ;;
  down)
    php_down
    docker compose -f "$COMPOSE_FILE" down -v
    ;;
  *)
    echo "Usage: $0 [up|down]"; exit 1;;
eseac
