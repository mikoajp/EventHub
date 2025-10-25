#!/bin/bash
# Test Environment Setup Script

set -e

echo "🧪 Setting up test environment..."

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Change to backend directory
cd "$(dirname "$0")/../backend" || exit 1

echo -e "${YELLOW}1. Checking dependencies...${NC}"
if [ ! -d "vendor" ]; then
    echo "Installing composer dependencies..."
    composer install --no-interaction
fi

echo -e "${YELLOW}2. Setting up test database...${NC}"
# Drop existing test database
php bin/console doctrine:database:drop --if-exists --force --env=test --quiet || true

# Create test database
php bin/console doctrine:database:create --env=test --quiet

# Create schema
php bin/console doctrine:schema:create --env=test --quiet

echo -e "${YELLOW}3. Loading test fixtures...${NC}"
php bin/console doctrine:fixtures:load --env=test --no-interaction --quiet || echo "Fixtures not available (optional)"

echo -e "${YELLOW}4. Clearing test cache...${NC}"
php bin/console cache:clear --env=test --quiet

echo -e "${GREEN}✅ Test environment ready!${NC}"
echo ""
echo "Run tests with:"
echo "  php bin/phpunit tests/Unit              # Unit tests"
echo "  php bin/phpunit tests/Integration       # Integration tests"
echo "  php bin/phpunit tests/Functional        # Functional tests"
echo "  php bin/phpunit                         # All tests"
