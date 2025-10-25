# Test Environment Setup Script (PowerShell)

Write-Host "🧪 Setting up test environment..." -ForegroundColor Cyan

# Change to backend directory
Set-Location "$PSScriptRoot\..\backend"

Write-Host "1. Checking dependencies..." -ForegroundColor Yellow
if (-not (Test-Path "vendor")) {
    Write-Host "Installing composer dependencies..."
    composer install --no-interaction
}

Write-Host "2. Setting up test database..." -ForegroundColor Yellow
# Drop existing test database
php bin/console doctrine:database:drop --if-exists --force --env=test --quiet 2>$null

# Create test database
php bin/console doctrine:database:create --env=test --quiet

# Create schema
php bin/console doctrine:schema:create --env=test --quiet

Write-Host "3. Loading test fixtures..." -ForegroundColor Yellow
php bin/console doctrine:fixtures:load --env=test --no-interaction --quiet 2>$null

Write-Host "4. Clearing test cache..." -ForegroundColor Yellow
php bin/console cache:clear --env=test --quiet

Write-Host "`n✅ Test environment ready!" -ForegroundColor Green
Write-Host ""
Write-Host "Run tests with:"
Write-Host "  php bin/phpunit tests/Unit              # Unit tests" -ForegroundColor Cyan
Write-Host "  php bin/phpunit tests/Integration       # Integration tests" -ForegroundColor Cyan
Write-Host "  php bin/phpunit tests/Functional        # Functional tests" -ForegroundColor Cyan
Write-Host "  php bin/phpunit                         # All tests" -ForegroundColor Cyan
