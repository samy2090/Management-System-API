# Task Management System API

A comprehensive task management system built with Laravel 12, featuring task dependencies, user roles, and comprehensive API endpoints.

## Features

- **User Management**: Authentication with Laravel Sanctum
- **Task Management**: Create, read, update, delete tasks
- **Task Dependencies**: Manage task relationships and dependencies
- **Role-based Access**: User roles and permissions
- **API Documentation**: RESTful API endpoints
- **XSS Protection**: Built-in security helpers
- **Queue Support**: Background job processing
- **Caching**: Redis-based caching system

## Requirements

- PHP 8.2 or higher
- Composer
- Node.js & npm
- MySQL 8.0 or SQLite
- Redis (optional, for caching and queues)

## Quick Start

### Option 1: Docker Setup (Recommended)

The easiest way to get started is using Docker:

```bash
# Clone the repository
git clone <repository-url>
cd task-management-api

# Copy environment file
cp .env.example .env.docker
cp .env.docker .env

# Build and start containers
docker-compose up -d

# Install dependencies
docker-compose exec app composer install

# Generate application key
docker-compose exec app php artisan key:generate

# Run migrations and seeders
docker-compose exec app php artisan migrate --seed
```

The application will be available at:
- **API**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081
- **Redis Commander**: http://localhost:8082

### Option 2: Local Development Setup

#### 1. Clone and Install Dependencies

```bash
# Clone the repository
git clone <repository-url>
cd task-management-api

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

#### 2. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

#### 3. Database Setup

**For SQLite (Default):**
```bash
# Create SQLite database
touch database/database.sqlite

# Run migrations and seeders
php artisan migrate --seed
```

**For MySQL:**
```bash
# Update .env file with MySQL credentials:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=task_management
# DB_USERNAME=your_username
# DB_PASSWORD=your_password

# Run migrations and seeders
php artisan migrate --seed
```

#### 4. Start Development Server

```bash
# Option A: Laravel built-in server
php artisan serve

# Option B: Full development environment (with queue, logs, and vite)
composer run dev
```

## Environment Configuration

### Key Environment Variables

```bash
# Application
APP_NAME="Task Management API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database (SQLite - Default)
DB_CONNECTION=sqlite

# Database (MySQL - Alternative)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_management
DB_USERNAME=root
DB_PASSWORD=

# Cache & Session
CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# Redis (Optional)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## Available Commands

### Development Commands

```bash
# Start development server
php artisan serve

# Run all development services (server, queue, logs, vite)
composer run dev

# Watch for file changes (Vite)
npm run dev

# Build assets for production
npm run build
```

### Database Commands

```bash
# Run migrations
php artisan migrate

# Run migrations with seeders
php artisan migrate --seed

# Rollback migrations
php artisan migrate:rollback

# Fresh migration (drops all tables)
php artisan migrate:fresh --seed
```

### Queue Commands

```bash
# Process queue jobs
php artisan queue:work

# List failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Cache Commands

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Testing

```bash
# Run all tests
php artisan test

# Run tests with coverage
composer run test

# Run specific test file
php artisan test tests/Feature/TaskTest.php

# Run PHPUnit directly
./vendor/bin/phpunit
```

## API Endpoints


## API Endpoints

All API endpoints are prefixed with `/api/v1/`.

### Authentication

| Method | Endpoint         | Description         |
|--------|------------------|---------------------|
| POST   | /v1/login        | Login user          |
| POST   | /v1/logout       | Logout user         |

### Tasks

| Method  | Endpoint                                 | Description                                 |
|---------|------------------------------------------|---------------------------------------------|
| GET     | /v1/tasks                                | Get all tasks                              |
| GET     | /v1/tasks/search/query                   | Search tasks                               |
| GET     | /v1/tasks/status/{status}                | Get tasks by status                        |
| GET     | /v1/tasks/overdue/list                   | Get overdue tasks                          |
| GET     | /v1/tasks/{id}                           | Get specific task (with access control)    |
| PATCH   | /v1/tasks/{id}/status                    | Update task status (with access control)   |
| PATCH   | /v1/tasks/{id}/update                    | User updates their assigned task           |
| POST    | /v1/tasks                                | Create task (manager only)                 |
| PUT     | /v1/tasks/{id}                           | Update task (manager only)                 |
| PATCH   | /v1/tasks/{id}                           | Update task (manager only)                 |
| DELETE  | /v1/tasks/{id}                           | Delete task (manager only)                 |
| POST    | /v1/tasks/{id}/assign                    | Assign task to user (manager only)         |
| POST    | /v1/tasks/{id}/unassign                  | Unassign task (manager only)               |
| GET     | /v1/tasks/unassigned/list                | View unassigned tasks (manager only)       |
| GET     | /v1/tasks/statistics/all                 | View all statistics (manager only)         |

### Task Dependencies

| Method  | Endpoint                                                        | Description                                 |
|---------|-----------------------------------------------------------------|---------------------------------------------|
| GET     | /v1/tasks/{task}/dependencies                                   | List dependencies for a task                |
| GET     | /v1/tasks/{task}/dependent-tasks                                | List tasks that depend on a task            |
| POST    | /v1/tasks/{task}/dependencies                                   | Add a dependency to a task (manager only)   |
| POST    | /v1/tasks/{task}/dependencies/multiple                          | Add multiple dependencies (manager only)    |
| DELETE  | /v1/tasks/{task}/dependencies/{dependsOnTaskId}                 | Remove a dependency (manager only)          |

### My Tasks

| Method  | Endpoint                | Description                |
|---------|-------------------------|----------------------------|
| GET     | /v1/my-tasks            | Get my assigned tasks      |
| GET     | /v1/my-tasks/statistics | Get my task statistics     |

**Note:** All endpoints require authentication except `/v1/login`. Manager-only endpoints require the user to have the manager role. Some endpoints require the user to be assigned to the task (access control).

## Docker Services

When using Docker, the following services are available:

| Service | Port | Description |
|---------|------|-------------|
| Nginx | 8080 | Web server |
| MySQL | 3306 | Database |
| Redis | 6379 | Cache & Queue |
| phpMyAdmin | 8081 | Database management |
| Redis Commander | 8082 | Redis management |

## Project Structure

```
app/
├── Enums/          # Enumerations (TaskStatus, UserRole)
├── Http/           # Controllers, Middleware, Requests, Resources
├── Models/         # Eloquent models
├── Policies/       # Authorization policies
├── Repositories/   # Data access layer
├── Services/       # Business logic layer
├── Observers/      # Model observers
└── Utils/          # Utility classes

database/
├── migrations/     # Database migrations
├── seeders/        # Database seeders
└── factories/      # Model factories

tests/
├── Feature/        # Feature tests
└── Unit/          # Unit tests
```

## Troubleshooting

### Common Issues

**Permission Issues (Linux/Mac):**
```bash
sudo chown -R $USER:www-data storage
sudo chown -R $USER:www-data bootstrap/cache
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

**Clear All Caches:**
```bash
php artisan optimize:clear
```

**Database Connection Issues:**
- Verify database credentials in `.env`
- Ensure database server is running
- Check if database exists

**Docker Issues:**
```bash
# Rebuild containers
docker-compose down
docker-compose up --build -d

# View logs
docker-compose logs app
```

## Development Workflow

1. **Start Development Environment:**
   ```bash
   composer run dev
   ```

2. **Make Changes**: Edit your code files

3. **Run Tests:**
   ```bash
   php artisan test
   ```

4. **Commit Changes:**
   ```bash
   git add .
   git commit -m "Your commit message"
   ```

## Production Deployment

### Docker Production Setup

```bash
# Build for production
docker-compose -f docker-compose.prod.yml up -d

# Or use the production environment file
cp .env.production .env
```

### Manual Production Setup

```bash
# Install dependencies (production only)
composer install --optimize-autoloader --no-dev

# Build assets
npm run build

# Cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
chmod -R 775 storage bootstrap/cache
```

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Support

For support, please open an issue in the GitHub repository or contact the development team.
