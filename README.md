# EventFlow

A production-style **multi-tenant Event & E-commerce SaaS** backend built with Laravel. EventFlow lets organizations manage events, sell merchandise, and handle customer orders — all within isolated tenant boundaries on a shared database.

## Features

- **Multi-tenancy** — shared-database isolation via `tenant_id` and a global scope; cross-tenant access returns 404
- **Role-based access** — Super Admin, Tenant Admin, Manager, Customer with policy-enforced permissions
- **Events** — CRUD, publishing workflow, customer registration with capacity checks
- **E-commerce** — product catalog, server-side order totals (tax + pricing), stock management, fake payment gateway
- **Dashboard** — tenant-scoped statistics for admins and managers
- **Queues & notifications** — database queue with jobs for orders and event registrations
- **Security** — Sanctum API tokens, rate limiting, production-safe error responses
- **Testing** — Pest feature tests covering auth, tenancy, policies, and business rules

## Tech stack

| Layer | Choice |
| --- | --- |
| Language | PHP 8.2+ |
| Framework | Laravel 12 |
| Database | MySQL 8 |
| Auth | Laravel Sanctum (API tokens) |
| Queue | Database driver |
| Testing | Pest 3 |

## Architecture

EventFlow is a **modular monolith**: one deployable Laravel app with domain logic grouped under `app/Modules/`.

```
HTTP → throttle → Sanctum → tenant context → Form Request → Policy → Controller → Service → Eloquent → API Resource
```

**Tenant isolation** is enforced at three layers:

1. `SetCurrentTenant` middleware sets context from the authenticated user
2. `BelongsToTenant` trait + `TenantScope` filters all tenant-owned queries
3. Policies authorize actions by role and ownership

## Requirements

- PHP 8.2+
- Composer 2
- MySQL 8
- Node.js 18+ (optional, for frontend assets)

## Installation

```bash
# Clone and install dependencies
composer install

# Environment
cp .env.example .env
php artisan key:generate

# Configure MySQL in .env
# DB_CONNECTION=mysql
# DB_DATABASE=eventflow
# DB_USERNAME=root
# DB_PASSWORD=

# Database
php artisan migrate:fresh --seed

# Start the server
php artisan serve
```

API base URL: `http://localhost:8000/api`

## Admin panel (Filament)

EventFlow includes a web admin UI powered by [Filament](https://filamentphp.com).

```
http://localhost:8000/admin
```

Log in with any **Tenant Admin**, **Manager**, or **Super Admin** seeded user (password: `password`). Customers cannot access the panel.

| Role | Admin access |
| --- | --- |
| Super Admin | Tenants + view all tenant data |
| Tenant Admin | Events, products, orders, users |
| Manager | Events, products (view), orders |
| Customer | No access |

> **PHP `intl` required:** XAMPP’s default PHP does **not** include `intl`, which Filament needs. Use **Homebrew PHP** to run the app:
>
> ```bash
> brew install php@8.2
> export PATH="/opt/homebrew/opt/php@8.2/bin:$PATH"   # Intel Mac: /usr/local/opt/php@8.2/bin
> php -m | grep intl                                # should print: intl
> php artisan serve
> ```
>
> Keep using **XAMPP MySQL** for the database; only switch the `php` command used for Artisan.

## Queue worker

Notifications and background jobs use the database queue. Run a worker alongside the app:

```bash
php artisan queue:work
```

## Running tests

```bash
composer test
# or
php artisan test --compact
```

Tests use an in-memory SQLite database and do not require MySQL.

## Code style

```bash
vendor/bin/pint
```

## Demo credentials

All seeded users use password: **`password`**

| Email | Role | Tenant |
| --- | --- | --- |
| `superadmin@eventflow.test` | Super Admin | — |
| `admin@alpha.eventflow.test` | Tenant Admin | Alpha Events |
| `manager@alpha.eventflow.test` | Manager | Alpha Events |
| `customer@eventflow.test` | Customer | Alpha Events |
| `admin@beta.eventflow.test` | Tenant Admin | Beta Gatherings |
| `customer@beta.eventflow.test` | Customer | Beta Gatherings |

### Quick API test

```bash
# Health check
curl http://localhost:8000/api/health

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"customer@eventflow.test","password":"password"}'
```

Full endpoint reference: **[docs/api.md](docs/api.md)**

## API overview

| Method | Endpoint | Auth | Description |
| --- | --- | --- | --- |
| `GET` | `/api/health` | — | Health check |
| `POST` | `/api/auth/login` | — | Issue Sanctum token |
| `POST` | `/api/auth/logout` | ✓ | Revoke token |
| `GET` | `/api/auth/me` | ✓ | Current user |
| `GET` | `/api/dashboard` | ✓ | Tenant statistics |
| `GET/POST/PUT/DELETE` | `/api/events` | ✓ | Event CRUD |
| `POST` | `/api/events/{id}/register` | ✓ | Event registration |
| `GET/POST/PUT/DELETE` | `/api/products` | ✓ | Product CRUD |
| `GET/POST` | `/api/orders` | ✓ | List / create orders |
| `GET` | `/api/orders/{id}` | ✓ | Order detail |

## Project structure

```
app/
├── Enums/              # EventStatus, OrderStatus, ProductStatus, UserRole
├── Http/
│   ├── Controllers/Api/
│   ├── Middleware/     # SetCurrentTenant
│   ├── Requests/       # Form request validation + authorization
│   └── Resources/      # API response transformers
├── Jobs/               # OrderCreated, OrderStatusChanged, EventRegistration
├── Models/
├── Modules/
│   ├── Auth/
│   ├── Dashboard/
│   ├── Events/
│   ├── Notifications/
│   ├── Orders/
│   └── Products/
├── Policies/
└── Shared/             # CurrentTenant, TenantScope, ApiResponse, exceptions

database/
├── factories/
├── migrations/
└── seeders/            # RoleSeeder, DemoDataSeeder

docs/
└── api.md              # Full API reference

tests/
└── Feature/            # Auth, tenancy, controllers, policies, jobs, security
```

## Configuration

| Variable | Default | Description |
| --- | --- | --- |
| `EVENTFLOW_TAX_RATE` | `0.10` | Tax rate applied to orders (10%) |
| `EVENTFLOW_SIMULATE_PAYMENT_FAILURE` | `false` | Force payment gateway to fail |
| `EVENTFLOW_LOGIN_RATE_LIMIT` | `5` | Login attempts per minute |
| `EVENTFLOW_API_RATE_LIMIT` | `60` | API requests per minute |

## License

MIT
