# Bumpa Loyalty Program API

A comprehensive Laravel-based API for managing a loyalty program system with achievements, badges, transactions, and cashback payments.

## 🏗️ Architecture Overview

This API provides a complete backend solution for a loyalty program featuring:

- **User Management**: User registration, authentication, and role-based access control
- **Loyalty Points**: Earn, redeem, and track loyalty points
- **Achievements**: Unlockable achievements with progress tracking
- **Badges**: Tiered badge system with requirements
- **Transactions**: Purchase tracking and processing
- **Cashback Payments**: Automated cashback processing
- **Admin Dashboard**: Comprehensive admin interface for program management

## 🚀 Quick Start

### Prerequisites

- Docker and Docker Compose
- PHP 8.4+ (if running locally)
- Composer (if running locally)
- PostgreSQL (if running locally)

### Environment Setup

1. **Copy the environment file:**
   ```bash
   cp .env.example .env
   ```

2. **Configure your environment variables:**
   ```bash
   # Database Configuration
   DB_CONNECTION=pgsql
   DB_HOST=postgres
   DB_PORT=5432
   DB_DATABASE=bumpa_loyalty
   DB_USERNAME=bumpa_user
   DB_PASSWORD=your_secure_password

   # Application Configuration
   APP_NAME="Bumpa Loyalty API"
   APP_ENV=local
   APP_KEY=base64:your_app_key_here
   APP_DEBUG=true
   APP_URL=http://laravel.test

   # API Configuration
   API_KEY=your_api_key_here
   ```

## 🐳 Docker Setup

### Option 1: Run API Only

The API can be started independently using Docker Compose:

```bash
# Start the API with all dependencies
docker compose --profile api up -d

# This will start:
# - Laravel API (laravel.test:80)
# - PostgreSQL database
# - Redis cache
# - Horizon queue worker
# - Scheduler
# - PgAdmin (optional)
# - Meilisearch (optional)
# - Mailpit (optional)
```

### Option 2: Run API + Client Together

From the root directory, start both services:

```bash
# Start both API and Client
docker compose --profile default up -d
```

### Option 3: Run Everything (Full Stack)

```bash
# Start all services including development tools
docker compose up -d
```

## 🔧 Local Development Setup

If you prefer to run the API locally without Docker:

### 1. Install Dependencies

```bash
composer install
```

### 2. Database Setup

```bash
# Create database
createdb bumpa_loyalty

# Run migrations
php artisan migrate

# Seed the database
php artisan db:seed
```

### 3. Start Development Server

```bash
# Start Laravel development server
php artisan serve

# Start Horizon (for queue processing)
php artisan horizon

# Start scheduler (in another terminal)
php artisan schedule:work
```

## 📊 Database Seeding

The API comes with comprehensive seeders that create realistic test data:

```bash
# Run all seeders
php artisan db:seed

# Run specific seeders
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=AchievementSeeder
php artisan db:seed --class=BadgeSeeder
php artisan db:seed --class=LoyaltyPointSeeder
php artisan db:seed --class=UserAchievementSeeder
php artisan db:seed --class=UserBadgeSeeder
```

### Seeded Data Includes:

- **Users**: 15+ users with various roles and profiles
- **Achievements**: 5+ unlockable achievements
- **Badges**: 4+ tiered badges (Bronze, Silver, Gold, Platinum)
- **Loyalty Points**: Realistic point distributions
- **User Achievements**: Random achievement assignments
- **User Badges**: Badge assignments based on point thresholds

## 🔐 Authentication

The API uses Laravel Sanctum for authentication:

### Default Admin User
- **Email**: `superadmin@example.com`
- **Password**: `password`
- **Role**: Super Admin

### API Authentication

```bash
# Login to get access token
curl -X POST "http://laravel.test/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "superadmin@example.com",
    "password": "password"
  }'

# Use token in subsequent requests
curl -X GET "http://laravel.test/api/v1/users" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## 📡 API Endpoints

### Authentication
- `POST /api/v1/auth/login` - User login
- `POST /api/v1/auth/logout` - User logout
- `GET /api/v1/auth/me` - Get current user

### Users
- `GET /api/v1/users` - List users (paginated)
- `GET /api/v1/users/{id}` - Get user details
- `POST /api/v1/users` - Create user
- `PUT /api/v1/users/{id}` - Update user
- `DELETE /api/v1/users/{id}` - Delete user

### Achievements
- `GET /api/v1/achievements` - List achievements
- `GET /api/v1/achievements/{id}` - Get achievement
- `POST /api/v1/achievements` - Create achievement
- `PUT /api/v1/achievements/{id}` - Update achievement
- `DELETE /api/v1/achievements/{id}` - Delete achievement

### Badges
- `GET /api/v1/badges` - List badges
- `GET /api/v1/badges/{id}` - Get badge
- `POST /api/v1/badges` - Create badge
- `PUT /api/v1/badges/{id}` - Update badge
- `DELETE /api/v1/badges/{id}` - Delete badge

### Loyalty Points
- `GET /api/v1/loyalty-points` - List loyalty points
- `GET /api/v1/loyalty-points/{id}` - Get loyalty points
- `POST /api/v1/loyalty-points` - Create loyalty points
- `PUT /api/v1/loyalty-points/{id}` - Update loyalty points
- `DELETE /api/v1/loyalty-points/{id}` - Delete loyalty points

### Transactions
- `GET /api/v1/transactions` - List transactions
- `GET /api/v1/transactions/{id}` - Get transaction
- `POST /api/v1/transactions` - Create transaction
- `PUT /api/v1/transactions/{id}` - Update transaction
- `DELETE /api/v1/transactions/{id}` - Delete transaction

### Cashback Payments
- `GET /api/v1/cashback-payments` - List cashback payments
- `GET /api/v1/cashback-payments/{id}` - Get cashback payment
- `POST /api/v1/cashback-payments` - Create cashback payment
- `PUT /api/v1/cashback-payments/{id}` - Update cashback payment
- `DELETE /api/v1/cashback-payments/{id}` - Delete cashback payment

## 🔄 Queue Processing

The API uses Laravel Horizon for queue management:

```bash
# Start Horizon (if running locally)
php artisan horizon

# Monitor queues
php artisan horizon:status
```

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Generate coverage report
php artisan test --coverage
```

## 📈 Monitoring & Debugging

### Laravel Telescope
Access the Telescope dashboard at `http://laravel.test/telescope` for:
- Request/Response monitoring
- Database query analysis
- Job queue monitoring
- Exception tracking

### Laravel Horizon Dashboard
Access the Horizon dashboard at `http://laravel.test/horizon` for:
- Queue monitoring
- Job statistics
- Failed job management
- Queue configuration

## 🛠️ Development Tools

### Code Quality
```bash
# Run PHPStan for static analysis
./vendor/bin/phpstan analyse

# Run Laravel Pint for code formatting
./vendor/bin/pint
```

### Database Management
```bash
# Access PgAdmin (if enabled)
# URL: http://localhost:8080
# Email: hello@example.com
# Password: secret
```

## 🚀 Production Deployment

### Environment Configuration
```bash
# Set production environment
APP_ENV=production
APP_DEBUG=false

# Generate application key
php artisan key:generate

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Queue Workers
```bash
# Start production queue workers
php artisan horizon
```

## 📝 API Documentation

The API includes comprehensive documentation generated with Scribe:

```bash
# Generate API documentation
php artisan scribe:generate

# Access documentation at
# http://laravel.test/docs
```

## 🔧 Troubleshooting

### Common Issues

1. **Database Connection Issues**
   ```bash
   # Check database connection
   php artisan tinker
   DB::connection()->getPdo();
   ```

2. **Permission Issues**
   ```bash
   # Fix storage permissions
   chmod -R 775 storage bootstrap/cache
   ```

3. **Queue Not Processing**
   ```bash
   # Restart Horizon
   php artisan horizon:terminate
   php artisan horizon
   ```

### Logs
```bash
# View application logs
tail -f storage/logs/laravel.log

# View Horizon logs
tail -f storage/logs/horizon.log
```

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [Laravel Horizon](https://laravel.com/docs/horizon)
- [Laravel Telescope](https://laravel.com/docs/telescope)
- [Spatie Permission](https://spatie.be/docs/laravel-permission)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License.
