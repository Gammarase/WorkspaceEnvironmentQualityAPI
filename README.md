# Workspace Environment Quality API

A high-performance Laravel 12 API application powered by Laravel Octane and OpenSwoole, containerized with Docker for seamless local development.

## Features

- **Laravel 12** with PHP 8.2+
- **Laravel Octane** with OpenSwoole for high-performance request handling
- **PostgreSQL 18** for robust data storage
- **Redis 7** for caching, sessions, and queues
- **pgAdmin 4** for database management
- **Dockerized** for consistent development environments
- **Live code synchronization** with bind mounts

## Quick Start

### Prerequisites

- [Docker](https://www.docker.com/get-started) and Docker Compose
- Git

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd WorkspaceEnvironmentQualityAPI
   ```

2. **Start the application**
   ```bash
   docker compose up -d
   ```

3. **Run database migrations**
   ```bash
   docker compose exec app php artisan migrate
   ```

4. **Access the application**
   - API: http://localhost
   - pgAdmin: http://localhost:8080

That's it! Your API is now running.

## Services

| Service | Port | Description |
|---------|------|-------------|
| Laravel API | 80 | Main application with Octane + OpenSwoole |
| PostgreSQL | 5432 | Database server |
| Redis | 6379 | Cache, sessions, and queue backend |
| pgAdmin | 8080 | Database management UI |

## Common Commands

### Application Management

```bash
# View logs
docker compose logs -f app

# Restart application
docker compose restart app

# Stop all services
docker compose stop

# Remove containers and volumes
docker compose down -v
```

### Laravel Commands

```bash
# Run migrations
docker compose exec app php artisan migrate

# Create a migration
docker compose exec app php artisan make:migration create_example_table

# Seed database
docker compose exec app php artisan db:seed

# Clear caches
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear

# Access tinker
docker compose exec app php artisan tinker

# Run tests
docker compose exec app php artisan test
```

### Database Management

**Using pgAdmin for dev:**
1. Navigate to http://localhost:8080
2. Login with:
   - Email: `admin@workspace.local`
   - Password: `password`
3. Add server:
   - Host: `pgsql`
   - Port: `5432`
   - Username: `workspaceapi`
   - Password: `password`

**Using CLI:**
```bash
# Access PostgreSQL shell
docker compose exec pgsql psql -U workspaceapi -d workspaceenvironmentqualityapi

# Execute SQL file
docker compose exec pgsql psql -U workspaceapi -d workspaceenvironmentqualityapi -f /path/to/file.sql
```

### Composer Commands

```bash
# Install dependencies
docker compose exec app composer install

# Add a package
docker compose exec app composer require vendor/package

# Update dependencies
docker compose exec app composer update
```

### Container Access

```bash
# Access app container shell
docker compose exec app bash

# Access as root
docker compose exec -u root app bash

# Check container status
docker compose ps
```

## Development

### Code Changes

Your local codebase is automatically synchronized with the container via bind mounts. Changes to PHP files are immediately available.

**After making changes, restart Octane:**
```bash
docker compose restart app
```

### Environment Configuration

Edit `.env` to configure your application. Key variables:

```env
# Application
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_DATABASE=workspaceenvironmentqualityapi
DB_USERNAME=workspaceapi
DB_PASSWORD=password

# Redis
REDIS_HOST=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Octane
OCTANE_SERVER=swoole
OCTANE_WORKERS=4
OCTANE_WATCH=false
```

### Docker Configuration

Override defaults in `.env` or create `.env.docker`:

```env
APP_PORT=80              # Host port for API
POSTGRES_PORT=5432       # Host port for PostgreSQL
REDIS_PORT=6379          # Host port for Redis
PGADMIN_PORT=8080        # Host port for pgAdmin
OCTANE_WORKERS=4         # Number of Octane workers
AUTO_MIGRATE=false       # Auto-run migrations on startup
COMPOSER_UPDATE=false    # Auto-update dependencies on startup
```

## Project Structure

```
├── app/                    # Application code
├── bootstrap/              # Framework bootstrap
├── config/                 # Configuration files
├── database/               # Migrations, seeders, factories
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── docker/                 # Docker configuration
│   ├── entrypoint.sh
│   └── octane.ini
├── public/                 # Public assets
├── resources/              # Views, raw assets
├── routes/                 # Route definitions
│   ├── api.php
│   └── web.php
├── storage/                # Logs, cache, uploads
├── tests/                  # Test files
├── .env                    # Environment variables
├── docker-compose.yml      # Docker orchestration
├── Dockerfile              # Container image
├── composer.json           # PHP dependencies
└── artisan                 # Laravel CLI
```

## Troubleshooting

### Permission Issues

If you encounter permission errors:
```bash
chmod -R ugo+rw ./vendor ./storage ./bootstrap/cache
```

### Port Already in Use

Change the port in `.env`:
```env
APP_PORT=8000
```

Then restart:
```bash
docker compose down
docker compose up -d
```

### Database Connection Failed

Ensure all services are healthy:
```bash
docker compose ps
```

Restart services if needed:
```bash
docker compose restart pgsql redis app
```

### Clear All Caches

```bash
docker compose exec app php artisan optimize:clear
```

### View Detailed Logs

```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f app
docker compose logs -f pgsql
```

## Testing

```bash
# Run all tests
docker compose exec app php artisan test

# Run specific test file
docker compose exec app php artisan test tests/Feature/ExampleTest.php

# Run with coverage
docker compose exec app php artisan test --coverage
```

## Production Deployment

**Before deploying to production:**

1. Update `.env`:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   ```

2. Set strong passwords for all services

3. Configure HTTPS/SSL certificates

4. Enable OPcache in `docker/octane.ini`

5. Optimize Laravel:
   ```bash
   docker compose exec app php artisan config:cache
   docker compose exec app php artisan route:cache
   docker compose exec app php artisan view:cache
   ```

6. Disable pgAdmin or restrict access

## Documentation

For detailed documentation, configuration options, performance tuning, and advanced usage, see [CLAUDE.md](CLAUDE.md).

## Tech Stack

- **Framework:** Laravel 12.0
- **Server:** Laravel Octane + OpenSwoole 25.2
- **Language:** PHP 8.4
- **Database:** PostgreSQL 18
- **Cache/Queue:** Redis 7
- **Containerization:** Docker & Docker Compose

## License

MIT

## Support

For issues and questions:
1. Check [CLAUDE.md](CLAUDE.md) for detailed documentation
2. Review Docker logs: `docker compose logs -f`
3. Ensure all containers are healthy: `docker compose ps`
