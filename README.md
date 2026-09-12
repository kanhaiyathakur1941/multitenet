# EventFlow

A production-style **multi-tenant Event & E-commerce SaaS** backend built with Laravel. EventFlow lets organizations manage events, sell merchandise, and handle customer orders — all within isolated tenant boundaries on a shared database.

## Live demo

| Resource | URL |
| --- | --- |
| **Customer login** | https://multitenet-production-nbyekk.laravel.cloud/ |
| **Customer portal** | https://multitenet-production-nbyekk.laravel.cloud/portal |
| **Admin panel** | https://multitenet-production-nbyekk.laravel.cloud/admin |
| **API health** | https://multitenet-production-nbyekk.laravel.cloud/api/health |
| **Client demo guide** | [docs/demo.md](docs/demo.md) |

Customer login: `customer@eventflow.test` / `password`  
Admin login: `admin@alpha.eventflow.test` / `password`

## Features

- **Multi-tenancy** — shared-database isolation via `tenant_id` and a global scope; cross-tenant access returns 404
- **Role-based access** — Super Admin, Tenant Admin, Manager, Customer with policy-enforced permissions
- **Events** — CRUD, publishing workflow, customer registration with capacity checks
- **Customer portal** — Blade web UI at `/` (login) and `/portal` (dashboard, events, shop, cart, Razorpay checkout)
- **E-commerce** — product catalog, server-side order totals (tax + pricing), stock management, fake or Razorpay test payments
- **Dashboard** — tenant-scoped statistics for admins and managers
- **Queues & notifications** — database queue with jobs; email notifications for orders and event registrations
- **Security** — Sanctum API tokens, rate limiting, production-safe error responses
- **Testing** — Pest feature tests covering auth, tenancy, policies, and business rules

## Tech stack

| Layer | Choice |
| --- | --- |
| Language | PHP 8.3+ |
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

- PHP 8.3+ (PHP 8.4+ recommended; Laravel Cloud uses PHP 8.5 by default)
- Composer 2
- MySQL 8
- Node.js 18+ (required for asset builds on deploy)

## Laravel Cloud deployment

Laravel Cloud defaults to **PHP 8.5**. This project requires `openspout/openspout` ^4.29 (PHP 8.3–8.5 compatible).

**Build command:**

```bash
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader && npm ci && npm run build
```

**Deploy command (first deploy):**

```bash
php artisan migrate --force --seed
```

**Deploy command (subsequent deploys):**

```bash
php artisan migrate --force
```

> Do **not** use `--seed` on every deploy after the first one. Seeding is only needed once when the database is empty.

**Required environment variables:**

| Variable | Value |
| --- | --- |
| `APP_KEY` | Generate with `php artisan key:generate --show` |
| `APP_URL` | Your `https://*.laravel.cloud` URL |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |

Attach a **Laravel MySQL** database to the environment (Cloud injects `DB_*` automatically).

**PHP runtime on Cloud:** use **PHP 8.4** or **8.5** (General Settings → Runtime). Do **not** use PHP 8.2 — this project requires PHP 8.3+.

**After deploy, verify:**

- `https://YOUR-APP.laravel.cloud/api/health`
- `https://YOUR-APP.laravel.cloud/admin` (login: `admin@alpha.eventflow.test` / `password`)

**Queue worker (required for emails/notifications):**

1. Open your environment on [cloud.laravel.com](https://cloud.laravel.com)
2. Click the **App** cluster on the infrastructure canvas
3. **Background processes** → **Add background process**
4. Command: `php artisan queue:work --sleep=3 --tries=3 --timeout=90`
5. Save and redeploy

See [docs/demo.md](docs/demo.md) for Razorpay test mode, mail setup, and a full client walkthrough.

## Installation

```bash
# Clone and install dependencies (use bin/composer — NOT XAMPP php)
./bin/composer install

# Environment
cp .env.example .env
./bin/php artisan key:generate

# Configure MySQL in .env
# DB_CONNECTION=mysql
# DB_DATABASE=eventflow
# DB_USERNAME=root
# DB_PASSWORD=

# Database
./bin/php artisan migrate:fresh --seed

# Start the server (Homebrew PHP 8.4+ with intl)
composer serve
```

> **Important:** Do not use XAMPP's `php` (8.2.4) for Composer or Artisan. It lacks `intl` and cannot satisfy PHP 8.3+ dependencies. Always use `./bin/php` or `./bin/composer`.

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

> **Use Homebrew PHP, not XAMPP PHP:** XAMPP’s `php` (8.2.4) lacks `intl` and cannot run this project. Install Homebrew PHP 8.4+ and use project wrappers:
>
> ```bash
> brew install php@8.4
> ./bin/php -v          # should show 8.4.x
> ./bin/php -m | grep intl
> composer serve        # or: ./bin/php artisan serve
> ```
>
> For Composer: `./bin/composer install` (never plain `composer` if it points to XAMPP).
>
> Keep using **XAMPP MySQL** for the database only.

## Queue worker

Notifications and background jobs use the database queue. Run a worker alongside the app:

```bash
./bin/php artisan queue:work
```

On **Laravel Cloud**, add a background process on the App cluster (see [docs/demo.md](docs/demo.md#queue-worker-on-laravel-cloud)).

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
| `EVENTFLOW_PAYMENT_DRIVER` | `fake` | `fake` or `razorpay` |
| `EVENTFLOW_SIMULATE_PAYMENT_FAILURE` | `false` | Force fake payment gateway to fail |
| `RAZORPAY_KEY_ID` | — | Razorpay test Key ID |
| `RAZORPAY_KEY_SECRET` | — | Razorpay test Key Secret |
| `RAZORPAY_CURRENCY` | `INR` | Razorpay order currency |
| `EVENTFLOW_MAIL_NOTIFICATIONS` | `true` | Send order/event emails to the customer’s address |
| `EVENTFLOW_DEMO_CUSTOMER_EMAIL` | `customer@eventflow.test` | Prefilled portal login / Razorpay demo email |
| `EVENTFLOW_DEMO_CUSTOMER_PHONE` | `9999999999` | Dummy phone for Razorpay checkout prefill |
| `EVENTFLOW_LOGIN_RATE_LIMIT` | `5` | Login attempts per minute |
| `EVENTFLOW_API_RATE_LIMIT` | `60` | API requests per minute |

## License

MIT
