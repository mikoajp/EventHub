#!/bin/bash

###############################################################################
# EventHub Development Environment Starter
# This script starts all necessary services for local development
###############################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default command
COMMAND=${1:-"up"}

# Determine script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$SCRIPT_DIR"

###############################################################################
# Helper Functions
###############################################################################

print_header() {
    echo -e "\n${BLUE}════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}════════════════════════════════════════════════════════${NC}\n"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ️  $1${NC}"
}

check_command() {
    if ! command -v "$1" &> /dev/null; then
        print_error "$1 is not installed"
        return 1
    fi
    return 0
}

wait_for_service() {
    local host=$1
    local port=$2
    local service=$3
    local max_attempts=30
    local attempt=0

    echo -n "Waiting for $service..."
    while [ $attempt -lt $max_attempts ]; do
        if nc -z "$host" "$port" 2>/dev/null; then
            print_success "$service is ready"
            return 0
        fi
        echo -n "."
        sleep 1
        ((attempt++))
    done
    print_error "Timeout waiting for $service"
    return 1
}

###############################################################################
# Main Functions
###############################################################################

check_prerequisites() {
    print_header "Checking Prerequisites"
    
    local missing_tools=0
    
    check_command "docker" || missing_tools=1
    check_command "docker-compose" || missing_tools=1
    check_command "php" || missing_tools=1
    check_command "composer" || missing_tools=1
    check_command "node" || missing_tools=1
    check_command "npm" || missing_tools=1
    check_command "nc" || missing_tools=1
    
    if [ $missing_tools -eq 1 ]; then
        print_error "Some required tools are missing. Please install them."
        return 1
    fi
    
    print_success "All prerequisites met"
    return 0
}

start_services() {
    print_header "Starting Docker Services"
    
    cd "$PROJECT_ROOT" || exit 1
    
    # Start docker compose services
    print_info "Starting Docker containers..."
    docker-compose -f docker-compose.test.yml up -d
    
    # Wait for services
    print_info "Waiting for services to be ready..."
    wait_for_service "127.0.0.1" "5433" "PostgreSQL" || return 1
    wait_for_service "127.0.0.1" "3306" "MySQL" || return 1
    wait_for_service "127.0.0.1" "6379" "Redis" || return 1
    wait_for_service "127.0.0.1" "5672" "RabbitMQ" || return 1
    wait_for_service "127.0.0.1" "3001" "Mercure" || return 1
    
    print_success "All Docker services are running"
}

setup_backend() {
    print_header "Setting Up Backend"
    
    cd "$PROJECT_ROOT/backend" || exit 1
    
    # Check if .env.local exists, if not create it from .env.dev
    if [ ! -f .env.local ]; then
        print_info "Creating .env.local from .env.dev..."
        cp .env.dev .env.local
    fi
    
    # Install dependencies
    print_info "Installing Composer dependencies..."
    composer install --no-interaction --optimize-autoloader
    
    # Run migrations
    print_info "Running database migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction || true
    
    # Create admin user if needed
    print_info "Setting up initial data..."
    php bin/console doctrine:fixtures:load --no-interaction || true
    
    print_success "Backend setup complete"
}

setup_frontend() {
    print_header "Setting Up Frontend"
    
    cd "$PROJECT_ROOT/frontend" || exit 1
    
    # Check if .env exists, if not create it
    if [ ! -f .env ]; then
        print_info "Creating .env..."
        cat > .env << 'EOF'
VITE_API_URL=http://localhost:8001/api
VITE_MERCURE_URL=http://localhost:3000/.well-known/mercure
EOF
    fi
    
    # Install dependencies
    print_info "Installing npm dependencies..."
    npm install
    
    print_success "Frontend setup complete"
}

start_backend() {
    print_header "Starting Backend Server"
    
    cd "$PROJECT_ROOT/backend" || exit 1
    
    print_info "Starting PHP development server on http://127.0.0.1:8001"
    php -S 127.0.0.1:8001 -t public > "$PROJECT_ROOT/backend-dev.log" 2>&1 &
    BACKEND_PID=$!
    echo $BACKEND_PID > "$PROJECT_ROOT/.backend.pid"
    
    sleep 2
    if ! wait_for_service "127.0.0.1" "8001" "Backend API"; then
        print_error "Failed to start backend"
        return 1
    fi
    
    print_success "Backend running at http://127.0.0.1:8001"
}

start_frontend() {
    print_header "Starting Frontend Server"
    
    cd "$PROJECT_ROOT/frontend" || exit 1
    
    print_info "Starting Vite development server on http://localhost:5173"
    npm run dev > "$PROJECT_ROOT/frontend-dev.log" 2>&1 &
    FRONTEND_PID=$!
    echo $FRONTEND_PID > "$PROJECT_ROOT/.frontend.pid"
    
    sleep 3
    if ! wait_for_service "127.0.0.1" "5173" "Frontend"; then
        print_error "Failed to start frontend"
        return 1
    fi
    
    print_success "Frontend running at http://localhost:5173"
}

display_info() {
    print_header "Development Environment Ready 🎉"
    
    echo -e "${GREEN}Services are running:${NC}"
    echo -e "  ${BLUE}API Backend:${NC}      http://127.0.0.1:8001"
    echo -e "  ${BLUE}Frontend:${NC}         http://localhost:5173"
    echo -e "  ${BLUE}Mercure:${NC}          http://localhost:3001"
    echo -e "  ${BLUE}PostgreSQL:${NC}       localhost:5433"
    echo -e "  ${BLUE}MySQL:${NC}            localhost:3306"
    echo -e "  ${BLUE}Redis:${NC}            localhost:6379"
    echo -e "  ${BLUE}RabbitMQ Admin:${NC}   http://localhost:15673"
    
    echo -e "\n${GREEN}Useful Commands:${NC}"
    echo -e "  View backend logs:   tail -f backend-dev.log"
    echo -e "  View frontend logs:  tail -f frontend-dev.log"
    echo -e "  Stop all services:   ./start-dev.sh down"
    
    echo -e "\n${GREEN}Testing Account:${NC}"
    echo -e "  Email:    admin@example.com"
    echo -e "  Password: (check your database)"
    
    echo ""
}

up() {
    check_prerequisites || exit 1
    start_services || exit 1
    setup_backend || exit 1
    setup_frontend || exit 1
    start_backend || exit 1
    start_frontend || exit 1
    display_info
    
    print_header "🎯 Keeping services running - Press Ctrl+C to stop"
    
    # Keep script running
    while true; do
        sleep 1
    done
}

down() {
    print_header "Stopping Development Environment"
    
    # Stop backend
    if [ -f "$PROJECT_ROOT/.backend.pid" ]; then
        BACKEND_PID=$(cat "$PROJECT_ROOT/.backend.pid")
        if kill -0 "$BACKEND_PID" 2>/dev/null; then
            kill "$BACKEND_PID"
            print_success "Backend stopped"
        fi
        rm "$PROJECT_ROOT/.backend.pid"
    fi
    
    # Stop frontend
    if [ -f "$PROJECT_ROOT/.frontend.pid" ]; then
        FRONTEND_PID=$(cat "$PROJECT_ROOT/.frontend.pid")
        if kill -0 "$FRONTEND_PID" 2>/dev/null; then
            kill "$FRONTEND_PID"
            print_success "Frontend stopped"
        fi
        rm "$PROJECT_ROOT/.frontend.pid"
    fi
    
    # Stop docker services
    print_info "Stopping Docker containers..."
    cd "$PROJECT_ROOT" || exit 1
    docker-compose -f docker-compose.test.yml down
    
    print_success "All services stopped"
}

logs() {
    print_header "Development Environment Logs"
    
    # Create temporary file to hold tail processes
    TEMP_FIFO=$(mktemp -t backend-logs.XXXXXX)
    TEMP_FIFO2=$(mktemp -t frontend-logs.XXXXXX)
    
    print_info "Showing logs for all services..."
    echo -e "${BLUE}Backend logs:${NC}"
    tail -f "$PROJECT_ROOT/backend-dev.log" &
    BACKEND_LOG_PID=$!
    
    echo -e "\n${BLUE}Frontend logs:${NC}"
    tail -f "$PROJECT_ROOT/frontend-dev.log" &
    FRONTEND_LOG_PID=$!
    
    # Wait for interrupt
    trap "kill $BACKEND_LOG_PID $FRONTEND_LOG_PID 2>/dev/null; exit" INT TERM
    wait
}

reset() {
    print_header "Resetting Development Environment"
    
    down
    
    print_info "Removing Docker volumes..."
    cd "$PROJECT_ROOT" || exit 1
    docker-compose -f docker-compose.test.yml down -v
    
    print_info "Removing local node_modules and vendor..."
    rm -rf "$PROJECT_ROOT/backend/vendor"
    rm -rf "$PROJECT_ROOT/frontend/node_modules"
    
    print_success "Environment reset"
}

status() {
    print_header "Environment Status"
    
    echo -e "${BLUE}Backend:${NC}"
    if [ -f "$PROJECT_ROOT/.backend.pid" ]; then
        BACKEND_PID=$(cat "$PROJECT_ROOT/.backend.pid")
        if kill -0 "$BACKEND_PID" 2>/dev/null; then
            echo "  ✅ Running (PID: $BACKEND_PID)"
        else
            echo "  ❌ Not running"
        fi
    else
        echo "  ❌ Not running"
    fi
    
    echo -e "\n${BLUE}Frontend:${NC}"
    if [ -f "$PROJECT_ROOT/.frontend.pid" ]; then
        FRONTEND_PID=$(cat "$PROJECT_ROOT/.frontend.pid")
        if kill -0 "$FRONTEND_PID" 2>/dev/null; then
            echo "  ✅ Running (PID: $FRONTEND_PID)"
        else
            echo "  ❌ Not running"
        fi
    else
        echo "  ❌ Not running"
    fi
    
    echo -e "\n${BLUE}Docker Services:${NC}"
    cd "$PROJECT_ROOT" || exit 1
    docker-compose -f docker-compose.test.yml ps
}

###############################################################################
# Main Script
###############################################################################

case "$COMMAND" in
    up)
        up
        ;;
    down)
        down
        ;;
    logs)
        logs
        ;;
    reset)
        reset
        ;;
    status)
        status
        ;;
    *)
        echo "Usage: $0 {up|down|logs|reset|status}"
        echo ""
        echo "Commands:"
        echo "  up      - Start development environment"
        echo "  down    - Stop development environment"
        echo "  logs    - Show development logs"
        echo "  reset   - Reset environment (clears volumes and dependencies)"
        echo "  status  - Show status of services"
        exit 1
        ;;
esac
