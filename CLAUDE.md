# Workspace Environment Quality API

Laravel 12 API application running on Laravel Octane with OpenSwoole in Docker containers.

## Architecture

### Technology Stack

- **Framework**: Laravel 12.0 (PHP 8.2+)
- **Server**: Laravel Octane with OpenSwoole 25.2 on PHP 8.4
- **Database**: PostgreSQL 18 Alpine
- **Cache/Queue/Sessions**: Redis 7 Alpine
- **Database Management**: pgAdmin 4

### Container Services

1. **app** (`workspace-api`)
   - Laravel application running on Octane + OpenSwoole
   - Port: 80 (HTTP)
   - Base image: `openswoole/swoole:25.2-php8.4-alpine`
   - User: `workspaceapi` (UID/GID 1000)

2. **pgsql** (`workspace-pgsql`)
   - PostgreSQL database server
   - Port: 5432
   - Image: `postgres:18-alpine`

3. **redis** (`workspace-redis`)
   - Redis for caching, sessions, and queues
   - Port: 6379
   - Image: `redis:7-alpine`
   - Configuration: 256MB max memory with LRU eviction

4. **pgadmin** (`workspace-pgadmin`)
   - Web-based PostgreSQL management interface
   - Port: 8080
   - Image: `dpage/pgadmin4:latest`

## Project Structure

```
WorkspaceEnvironmentQualityAPI/
├── app/                        # Laravel application code
├── bootstrap/                  # Bootstrap files
├── config/                     # Configuration files
│   └── octane.php             # Octane configuration
├── database/                   # Migrations, seeders, factories
├── docker/                     # Docker-specific files
│   ├── entrypoint.sh          # Container initialization script
│   └── octane.ini             # PHP configuration for Octane
├── public/                     # Public assets
├── resources/                  # Views, assets
├── routes/                     # API routes
├── storage/                    # Logs, cache, sessions
├── tests/                      # Test files
├── vendor/                     # Composer dependencies
├── .dockerignore              # Docker build exclusions
├── .env                       # Environment configuration
├── .gitignore                 # Git exclusions
├── artisan                    # Laravel CLI
├── composer.json              # PHP dependencies
├── docker-compose.yml         # Docker orchestration
├── Dockerfile                 # Container image definition
└── CLAUDE.md                  # This file
```

## Getting Started

### Prerequisites

- Docker and Docker Compose
- Git

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd WorkspaceEnvironmentQualityAPI
   ```

2. **Start Docker containers**
   ```bash
   docker compose up -d
   ```

3. **Check container status**
   ```bash
   docker compose ps
   ```

4. **View application logs**
   ```bash
   docker compose logs -f app
   ```

### First-Time Setup

The entrypoint script automatically handles:
- Waiting for PostgreSQL and Redis to be ready
- Installing Composer dependencies (if vendor/ doesn't exist)
- Generating application key (if not set)
- Clearing Laravel caches

To run migrations manually:
```bash
docker compose exec app php artisan migrate
```

## Development Workflow

### Accessing Services

- **API**: http://localhost
- **pgAdmin**: http://localhost:8080
  - Email: `admin@workspace.local`
  - Password: `password`

### Common Commands

**Execute Artisan commands**
```bash
docker compose exec app php artisan <command>
```

**Access container shell**
```bash
docker compose exec app bash
```

**Run Composer commands**
```bash
docker compose exec app composer <command>
```

**View logs**
```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f app
docker compose logs -f pgsql
docker compose logs -f redis
```

**Restart services**
```bash
# Restart all
docker compose restart

# Restart specific service
docker compose restart app
```

**Stop services**
```bash
docker compose stop
```

**Remove containers and volumes**
```bash
docker compose down -v
```

### Live Code Reload

The application directory is mounted as a volume at `/var/www/html` in the container, enabling live code synchronization. However, **hot reload (file watching) is currently disabled** because:
- File watching tools (fswatch/chokidar) are not available in Alpine Linux
- Node.js is not installed in the container
- File watching is not essential for development with bind mounts

To reload the application after code changes:
```bash
docker compose restart app
```

## Configuration

### Environment Variables

Key environment variables in `.env`:

```env
# Database
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=workspaceenvironmentqualityapi
DB_USERNAME=workspaceapi
DB_PASSWORD=password

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Octane
OCTANE_SERVER=swoole
OCTANE_HOST=0.0.0.0
OCTANE_PORT=80
OCTANE_WORKERS=4
OCTANE_MAX_REQUESTS=500
OCTANE_WATCH=false
```

### Docker Compose Variables

Override defaults in `.env` or create `.env.docker`:

```env
# Application
APP_PORT=80
USER_ID=1000
GROUP_ID=1000

# PostgreSQL
POSTGRES_DB=workspaceenvironmentqualityapi
POSTGRES_USER=workspaceapi
POSTGRES_PASSWORD=password
POSTGRES_PORT=5432

# Redis
REDIS_PORT=6379

# pgAdmin
PGADMIN_PORT=8080
PGADMIN_EMAIL=admin@workspace.local
PGADMIN_PASSWORD=password

# Octane
OCTANE_WORKERS=4
OCTANE_MAX_REQUESTS=500
OCTANE_WATCH=false

# Initialization
AUTO_MIGRATE=false
COMPOSER_UPDATE=false
```

### Octane Configuration

File watching is disabled in `config/octane.php`:
```php
'watch' => [
    // File watching disabled - not available in Alpine Linux container
],
```

PHP settings for Octane in `docker/octane.ini`:
- Memory limit: 256M
- Upload max filesize: 20M
- OPcache: Disabled (for development)

## Database Management

### Using pgAdmin

1. Access pgAdmin at http://localhost:8080
2. Log in with credentials from `.env`
3. Add server:
   - Name: workspace-pgsql
   - Host: pgsql
   - Port: 5432
   - Username: workspaceapi
   - Password: password

### Direct PostgreSQL Access

```bash
docker compose exec pgsql psql -U workspaceapi -d workspaceenvironmentqualityapi
```

### Database Migrations

```bash
# Run migrations
docker compose exec app php artisan migrate

# Rollback
docker compose exec app php artisan migrate:rollback

# Fresh migration
docker compose exec app php artisan migrate:fresh

# With seeding
docker compose exec app php artisan migrate:fresh --seed
```

## Troubleshooting

### Container Won't Start

Check logs:
```bash
docker compose logs app
```

Common issues:
- Port 80 already in use: Change `APP_PORT` in `.env`
- Permission errors: Run `chmod -R ugo+rw ./vendor ./storage ./bootstrap/cache`

### Database Connection Failed

1. Ensure PostgreSQL is healthy:
   ```bash
   docker compose ps pgsql
   ```

2. Check database credentials in `.env` match docker-compose.yml

3. Restart containers:
   ```bash
   docker compose restart app pgsql
   ```

### Redis Connection Failed

1. Check Redis is running:
   ```bash
   docker compose ps redis
   ```

2. Test Redis connection:
   ```bash
   docker compose exec redis redis-cli ping
   ```

### Application Key Missing

Generate a new key:
```bash
docker compose exec app php artisan key:generate
```

### Permission Issues

If you encounter permission errors with vendor, storage, or cache:
```bash
chmod -R ugo+rw ./vendor ./storage ./bootstrap/cache ./config
```

### Clear All Caches

```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear
```

## Performance Tuning

### Octane Workers

Adjust worker count based on your CPU cores:
```env
OCTANE_WORKERS=4
```

### Redis Memory

Edit docker-compose.yml:
```yaml
command: redis-server --appendonly yes --maxmemory 256mb --maxmemory-policy allkeys-lru
```

### PHP Memory Limit

Edit `docker/octane.ini`:
```ini
memory_limit = 256M
```

## Security Notes

### Production Deployment

Before deploying to production:

1. **Change all default passwords** in `.env`
2. **Set APP_ENV=production** and **APP_DEBUG=false**
3. **Use strong database passwords**
4. **Configure HTTPS** (set OCTANE_HTTPS=true and configure SSL certificates)
5. **Disable pgAdmin** or restrict access
6. **Review .gitignore** to ensure secrets aren't committed
7. **Enable OPcache** in octane.ini:
   ```ini
   opcache.enable = 1
   opcache.enable_cli = 1
   ```

### File Permissions

The application runs as user `workspaceapi` (UID 1000). Ensure proper permissions on:
- storage/
- bootstrap/cache/
- vendor/

## Container Health Checks

All services have health checks configured:

- **app**: HTTP request to localhost:80
- **pgsql**: `pg_isready` command
- **redis**: `redis-cli ping`

View health status:
```bash
docker compose ps
```

## Volumes

Persistent data volumes:
- `pgsql-data`: PostgreSQL database files
- `redis-data`: Redis persistence
- `pgadmin-data`: pgAdmin configuration

Bind mounts:
- `.:/var/www/html`: Application code (live sync)
- `./docker/octane.ini:/usr/local/etc/php/conf.d/octane.ini`: PHP config

## Network

All containers communicate via the `workspace-network` bridge network. Services are accessible by their container names:
- Database host: `pgsql`
- Redis host: `redis`
- Application: `workspace-api`

## PHP Extensions

Installed PHP extensions:
- pdo_pgsql
- pgsql
- redis
- pcntl
- zip
- mbstring
- xml
- bcmath
- intl
- gd
- exif
- openswoole (from base image)

## License

MIT
