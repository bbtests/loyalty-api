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
# - Mailpit (http://localhost:8025) - Email testing
# - PgAdmin (optional)
# - Meilisearch (optional)

# After startup, run migrations and seed the database
docker compose exec  laravel.test php artisan migrate:fresh --seed
```

### Option 2: Run API + Client Together (Full Stack)

This option runs both the Laravel API and Next.js client together. You need both repositories cloned in the correct structure.

#### Project Structure Setup

First, ensure you have the correct project structure:

```
bumpa/
├── api/                    # Laravel API (this repository)
│   ├── app/
│   │   ├── Contracts/      # Payment provider interfaces
│   │   ├── Events/         # Application events
│   │   ├── Http/           # Controllers, middleware, requests
│   │   ├── Jobs/           # Queue jobs
│   │   ├── Listeners/      # Event listeners
│   │   ├── Models/         # Eloquent models
│   │   ├── Notifications/ # Email notifications
│   │   ├── Observers/      # Model observers
│   │   ├── Policies/       # Authorization policies
│   │   ├── Providers/      # Service providers
│   │   ├── Services/       # Business logic services
│   │   └── Traits/         # Reusable traits
│   ├── bootstrap/          # Application bootstrap
│   ├── config/             # Configuration files
│   ├── database/
│   │   ├── factories/      # Model factories
│   │   ├── migrations/     # Database migrations
│   │   └── seeders/        # Database seeders
│   ├── docs/               # Documentation
│   ├── routes/             # API routes
│   ├── tests/              # Test suites
│   ├── docker-compose.yml  # Main compose file for full stack
│   └── ...
└── client/                 # Next.js Client (separate repository)
    ├── app/                # Next.js app router
    │   ├── auth/           # Authentication pages
    │   ├── dashboard/      # Dashboard pages
    │   └── api/            # API routes
    ├── components/         # React components
    │   ├── admin/          # Admin-specific components
    │   ├── payment/        # Payment components
    │   └── ui/             # Reusable UI components
    ├── store/              # Redux store slices
    ├── hooks/              # Custom React hooks
    ├── lib/                # Utility libraries
    ├── types/              # TypeScript type definitions
    ├── __tests__/          # Test files
    ├── e2e/                # End-to-end tests
    ├── package.json
    ├── docker-compose.yml
    └── ...
```

#### Cloning Instructions

1. **Clone the API repository:**
   ```bash
   git clone https://github.com/bbtests/loyalty-api.git api
   ```

2. **Clone the Client repository:**
   ```bash
   # From the bumpa directory
   git clone https://github.com/bbtests/loyalty-client.git client
   ```

   **Note:** The client repository is a Next.js application with TypeScript, featuring:
   - Admin dashboard with user management
   - Loyalty program interface
   - Authentication system with NextAuth.js
   - Redux Toolkit Query for state management
   - shadcn/ui components with Tailwind CSS

3. **Verify the structure:**
   ```bash
   ls -la
   # Should show: api/ and client/ directories
   
   # Navigate to api directory to run Docker commands
   cd api
   ls -la docker-compose.yml
   # Should show the main docker-compose.yml file
   ```

#### Running the Full Stack

From the `api/` directory (where the main docker-compose.yml is located):

```bash
# Navigate to the api directory
cd api

# Start both API and Client
docker compose --profile default up -d

# After startup, run migrations and seed the database
docker compose exec  laravel.test php artisan migrate:fresh --seed
```

This will start:
- Laravel API (localhost:80)
- Next.js Client (localhost:3000)
- PostgreSQL database
- Redis cache
- Horizon queue worker
- Scheduler
- Mailpit (http://localhost:8025) - Email testing

#### Troubleshooting Full Stack Setup

**Issue: "docker-compose.yml not found"**
```bash
# Make sure you're in the api/ directory
pwd
# Should show: /path/to/bumpa/api

# Check if docker-compose.yml exists
ls -la docker-compose.yml
```

**Issue: "Client not starting"**
```bash
# From the api/ directory, verify client directory exists
ls -la ../client/

# Check if client has its own docker-compose.yml
ls -la ../client/docker-compose.yml
```

**Issue: "Port conflicts"**
```bash
# Check if ports are already in use
lsof -i :3000  # Next.js client
lsof -i :80    # Laravel API
lsof -i :8025  # Mailpit
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
- **Password**: `P@ssword!`
- **Role**: Super Admin

### Additional Seed Users (from `UserSeeder`)
When you run the seeders, we also create additional sample users you can use for testing:

- John Smith — Email: `john.smith@example.com`, Password: `password`
- Sarah Johnson — Email: `sarah.johnson@example.com`, Password: `password`
- Mike Wilson — Email: `mike.wilson@example.com`, Password: `password`

The seeder also generates 15 random users via factories.

Run just this seeder with:
```bash
php artisan db:seed --class=UserSeeder
```

### API Authentication

```bash
# Login to get access token
curl -X POST "http://laravel.test/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "superadmin@example.com",
    "password": "P@ssword!"
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

## 📧 Email & Notification Handling

### Mailpit - Email Testing & Development

The API uses **Mailpit** for email testing and development. Mailpit is a lightweight SMTP testing tool that captures all outgoing emails without actually sending them.

#### Accessing Mailpit

When running with Docker, Mailpit is available at:
- **Web Interface**: `http://localhost:8025`
- **SMTP Server**: `localhost:1025`

#### Features

- **Email Capture**: All outgoing emails are captured and displayed in the web interface
- **Email Preview**: View HTML and text versions of emails
- **Email Search**: Search through captured emails by sender, recipient, or content
- **Email Download**: Download emails as `.eml` files for testing
- **SMTP Testing**: Test email sending without external dependencies

#### Configuration

Mailpit is automatically configured in the Docker setup. For local development, update your `.env` file:

```bash
# Mail Configuration for Mailpit
MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@bumpa.com"
MAIL_FROM_NAME="${APP_NAME}"
```

#### Testing Notifications

The API sends various types of notifications:

1. **Achievement Unlocked**: When users unlock new achievements
2. **Badge Earned**: When users earn new badges
3. **Cashback Processed**: When cashback payments are processed
4. **Welcome Emails**: New user registration confirmations
5. **Password Reset**: Password reset links and confirmations

#### Viewing Notifications in Mailpit

1. **Access Mailpit**: Navigate to `http://localhost:8025`
2. **View Emails**: All sent emails appear in the inbox
3. **Email Details**: Click on any email to view:
   - Sender and recipient information
   - Email subject and content
   - HTML and text versions
   - Email headers and metadata
4. **Search & Filter**: Use the search bar to find specific emails
5. **Download**: Save emails as `.eml` files for testing

#### Email Templates

The API includes customizable email templates for:
- Achievement notifications with badge icons
- Badge earned notifications with tier information
- Cashback payment confirmations with transaction details
- Welcome emails with onboarding information

#### Development Workflow

```bash
# 1. Start the API with Mailpit
docker compose --profile api up -d

# 2. Trigger an action that sends an email (e.g., unlock achievement)
curl -X POST "http://laravel.test/api/v1/achievements/unlock" \
  -H "Authorization: Bearer YOUR_TOKEN"

# 3. Check Mailpit for the notification
# Visit: http://localhost:8025
```

#### Production Email Configuration

For production, replace Mailpit with a real SMTP service:

```bash
# Production Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
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

### Email Testing
```bash
# Access Mailpit for email testing
# URL: http://localhost:8025
# All outgoing emails are captured here
# No login required - just view captured emails
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
