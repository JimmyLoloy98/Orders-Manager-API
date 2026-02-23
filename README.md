# Setup Instructions

## Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 5.7 or higher (recommended for cPanel)

## Installation Steps

### 1. Install Dependencies

```bash
composer install
```

### 2. Install Laravel Sanctum

Laravel Sanctum is required for API authentication. Install it with:

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### 3. Configure Environment

Copy the `.env.example` file to `.env`:

```bash
cp .env.example .env
php artisan key:generate
```

Update your database configuration in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=orders_manager
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Run Migrations

```bash
php artisan migrate
```

**Important:** The migrations are designed to run in order. The companies table will be created first, then the users table will be modified to add the company relationship.

### 5. Create API Token (Optional - for testing)

You can create a test user and company through tinker or seeders:

```bash
php artisan tinker
```

Then create a company and user:

```php
$company = App\Models\Company::create(['name' => 'Test Company']);
$user = App\Models\User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => bcrypt('password'),
    'company_id' => $company->id,
]);
```

### 6. Start the Development Server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000/api/v1`

## API Endpoints

All endpoints are documented in `API_ENDPOINTS.md`. The base URL is `/api/v1`.

### Authentication

- `POST /api/v1/auth/login` - Login and get token
- `POST /api/v1/auth/logout` - Logout (requires authentication)

### Protected Endpoints

All other endpoints require authentication. Include the token in the Authorization header:

```
Authorization: Bearer {token}
```

## Testing the API

You can test the API using tools like Postman, Insomnia, or curl:

```bash
# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'

# Use the token from the response
curl -X GET http://localhost:8000/api/v1/dashboard/stats \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Notes

- All endpoints automatically filter data by the authenticated user's `company_id`
- Client debt is automatically calculated when credits or payments are created/updated
- The API uses Laravel Sanctum for token-based authentication
