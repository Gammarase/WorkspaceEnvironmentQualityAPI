#!/bin/bash
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Starting Laravel Octane Container...${NC}"

# Set permissions (run as root before switching to workspaceapi user)
if [ "$(id -u)" = "0" ]; then
    echo -e "${YELLOW}Setting file permissions...${NC}"
    chown -R workspaceapi:workspaceapi /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
fi

# Wait for PostgreSQL
echo -e "${YELLOW}Waiting for PostgreSQL...${NC}"
until pg_isready -h pgsql -U ${DB_USERNAME:-workspaceapi} -d ${DB_DATABASE:-workspaceenvironmentqualityapi} > /dev/null 2>&1; do
    echo "Waiting for PostgreSQL to be ready..."
    sleep 2
done
echo -e "${GREEN}PostgreSQL is ready!${NC}"

# Wait for Redis
echo -e "${YELLOW}Waiting for Redis...${NC}"
until redis-cli -h redis ping > /dev/null 2>&1; do
    echo "Waiting for Redis to be ready..."
    sleep 1
done
echo -e "${GREEN}Redis is ready!${NC}"

# Install/update dependencies (only if vendor doesn't exist or COMPOSER_UPDATE=true)
if [ ! -d "vendor" ] || [ "$COMPOSER_UPDATE" = "true" ]; then
    echo -e "${YELLOW}Installing Composer dependencies...${NC}"
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Generate app key if not exists
if grep -q "APP_KEY=$" .env 2>/dev/null || [ -z "${APP_KEY}" ]; then
    echo -e "${YELLOW}Generating application key...${NC}"
    php artisan key:generate --ansi
fi

# Clear caches
echo -e "${YELLOW}Clearing caches...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear 2>/dev/null || true

# Run migrations (only if AUTO_MIGRATE=true)
if [ "$AUTO_MIGRATE" = "true" ]; then
    echo -e "${YELLOW}Running database migrations...${NC}"
    php artisan migrate --force
fi

# Check if Octane is installed
if ! php artisan list | grep -q "octane:start"; then
    echo -e "${RED}Laravel Octane is not installed!${NC}"
    echo -e "${YELLOW}Please run: docker compose exec app composer require laravel/octane${NC}"
    echo -e "${YELLOW}Then: docker compose exec app php artisan octane:install --server=swoole${NC}"
    echo -e "${YELLOW}Waiting for manual installation...${NC}"

    # Keep container running for manual intervention
    tail -f /dev/null
fi

# Start Octane
echo -e "${GREEN}Starting Laravel Octane with OpenSwoole...${NC}"

# Build Octane command
OCTANE_CMD="php artisan octane:start --host=${OCTANE_HOST:-0.0.0.0} --port=${OCTANE_PORT:-80} --workers=${OCTANE_WORKERS:-4} --max-requests=${OCTANE_MAX_REQUESTS:-500}"

# Add --watch flag if enabled
if [ "${OCTANE_WATCH}" = "true" ]; then
    OCTANE_CMD="${OCTANE_CMD} --watch"
fi

# Execute Octane (replaces this script process)
exec $OCTANE_CMD
