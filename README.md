<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Admin API

REST API backend for the Administration. Built with Laravel and secured via Laravel Sanctum.

## Requirements

| Tool     | Version |
|----------|---------|
| PHP      | 8.3.0   |
| Composer | 2.9.5   |
| Laravel  | 13.1.1  |

## Installation

### 1. Clone & install dependencies

```bash
git clone <repository-url> admin-api
```

Or create a new project with:

```bash
composer create-project laravel/laravel admin-api
```

After:

```bash
cd admin-api
composer install
```

### 2. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database credentials and any other environment-specific values.

### 3. Install API (Sanctum)

```bash
php artisan install:api
```

This will:
- Install **Laravel Sanctum** for API token authentication
- Create `routes/api.php`
- Register API routes in the bootstrap configuration
- Create the migration for personal access tokens
- Add `HasApiTokens` to your `User` model

Ensure your `app/Models/User.php` includes:

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
    // ...
}
```

### 4. Install additional packages

```bash
composer require chillerlan/php-qrcode
composer require league/flysystem-aws-s3-v3
composer require dedoc/scramble
```

### 5. Run migrations

```bash
php artisan migrate
php artisan migrate:install
php artisan schema:dump
php artisan migrate --seed
```

### 6. (Optional) Install frontend dependencies

```bash
npm install
npm run dev
```

### 7. Start the development server

```bash
php artisan serve --host=localhost --port=8080
```

The API will be available at `http://localhost:8080`. All routes defined in `routes/api.php` are automatically prefixed with `/api`.

Alternatively, using PHP's built-in server:

```bash
php -S 127.0.0.1:8080 -t public
```

## Laravel Operations

### Autoload refresh

After adding new classes or updating composer dependencies:

```bash
composer dump-autoload
```

### Migrations

> Reference: https://laravel.com/docs/migrations

**Fresh install** — run all pending migrations on a new database:

```bash
php artisan migrate
```

**Development reset** ⚠️ — drops all tables, re-runs all migrations, and seeds. Destructive, dev only:

```bash
php artisan migrate:fresh --seed
```

**Routine update** — apply new migrations on an existing database:

```bash
php artisan migrate
```

**Squash migrations** — dumps the current schema to a SQL file and (optionally) removes the individual migration files it replaces. Run only after all pending migrations have been applied:

```bash
php artisan schema:dump          # consolidate without removing files
php artisan schema:dump --prune  # consolidate and remove squashed files
```

## Composer Scripts

Convenience scripts defined in `composer.json`. Run with `composer <script>`.

| Script | Command | Description |
|--------|---------|-------------|
| `setup` | `composer setup` | Full first-time project setup: installs PHP & JS dependencies, copies `.env`, generates app key, runs migrations, and builds frontend assets. |
| `dev` | `composer dev` | Starts the full development environment in parallel: Laravel server, queue worker, Pail log viewer, and Vite. |
| `test` | `composer test` | Clears config cache, then runs the PHPUnit test suite via `php artisan test`. |
| `clear-all` | `composer clear-all` | Deep cache wipe: clears config, route, view, event, application cache, compiled views, sessions, and residual cache files. |

## API Documentation

API docs are generated automatically via [Scramble](https://scramble.dedoc.co/):

```
GET /docs/api
```

## Testing

```bash
php artisan test
```

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

Admin API is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
