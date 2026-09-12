# EventFlow API Reference

EventFlow is a multi-tenant Event & E-commerce SaaS backend. All endpoints are JSON REST APIs under `/api`.

## Base URL

**Live demo:**

```
https://multitenet-production-nbyekk.laravel.cloud/api
```

**Local development:**

```
http://localhost:8000/api
```

Client walkthrough: [demo.md](demo.md)

## Authentication

Protected routes require a **Laravel Sanctum** personal access token.

1. `POST /api/auth/login` with email and password.
2. Use the returned token on subsequent requests:

```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

Logout revokes the current token (`POST /api/auth/logout`).

### Demo credentials

All seeded users use password: `password`

| Email | Role | Tenant |
| --- | --- | --- |
| `superadmin@eventflow.test` | Super Admin | — |
| `admin@alpha.eventflow.test` | Tenant Admin | Alpha Events |
| `manager@alpha.eventflow.test` | Manager | Alpha Events |
| `customer@eventflow.test` | Customer | Alpha Events |
| `admin@beta.eventflow.test` | Tenant Admin | Beta Gatherings |
| `customer@beta.eventflow.test` | Customer | Beta Gatherings |

Seed data: `php artisan migrate:fresh --seed`

---

## Response envelope

### Success

```json
{
  "success": true,
  "message": "Human-readable message.",
  "data": {}
}
```

`data` is omitted when empty (e.g. logout).

### Paginated success

```json
{
  "success": true,
  "message": "Events retrieved.",
  "data": {
    "items": [],
    "meta": {
      "current_page": 1,
      "per_page": 15,
      "total": 42,
      "last_page": 3
    }
  }
}
```

Default `per_page` is **15** (max **50** where supported).

### Error

```json
{
  "success": false,
  "message": "Error summary.",
  "errors": {
    "field": ["Validation message."]
  }
}
```

`errors` appears on validation failures (`422`) only.

### Common HTTP status codes

| Code | Meaning |
| --- | --- |
| `200` | OK |
| `201` | Created |
| `401` | Unauthenticated (missing or invalid token) |
| `403` | Forbidden (authenticated but not permitted) |
| `404` | Resource not found (includes cross-tenant access) |
| `422` | Validation error |
| `429` | Too many requests (rate limited) |
| `500` | Server error (generic message when `APP_DEBUG=false`) |

---

## Rate limiting

| Limiter | Scope | Default |
| --- | --- | --- |
| `login` | Per email + IP | 5 requests / minute |
| `api` | Per user ID (or IP if unauthenticated) | 60 requests / minute |

Configure via `.env`:

```
EVENTFLOW_LOGIN_RATE_LIMIT=5
EVENTFLOW_API_RATE_LIMIT=60
```

---

## Multi-tenancy

- Tenant context is derived from the authenticated user's `tenant_id`.
- All tenant-owned data (events, products, orders, registrations) is scoped automatically.
- Requesting another tenant's resource ID returns **404**, not 403.
- **Super Admin** has no tenant context and cannot access tenant-scoped APIs.
- Users without a tenant or on an inactive tenant receive **403**.

---

## Roles & permissions

| Action | Super Admin | Tenant Admin | Manager | Customer |
| --- | --- | --- | --- | --- |
| View dashboard | ✗ | ✓ | ✓ | ✗ |
| Manage events (CRUD) | ✗ | ✓ | ✓ | ✗ |
| View published events | ✗ | ✓ | ✓ | ✓ |
| View draft events | ✗ | ✓ | ✓ | ✗ |
| Register for event | ✗ | ✗ | ✗ | ✓ |
| Manage products (CRUD) | ✗ | ✓ | ✗ | ✗ |
| View active products | ✗ | ✓ | ✓ | ✓ |
| Create order | ✗ | ✗ | ✗ | ✓ |
| View own orders | ✗ | ✗ | ✗ | ✓ |
| View all tenant orders | ✗ | ✓ | ✓ | ✗ |

---

## Endpoints

### Health

#### `GET /api/health`

Public. No authentication required.

**Response `200`**

```json
{
  "success": true,
  "message": "EventFlow API is running.",
  "data": {
    "status": "ok"
  }
}
```

---

### Authentication

#### `POST /api/auth/login`

Public. Rate limited (`throttle:login`).

**Body**

| Field | Type | Required |
| --- | --- | --- |
| `email` | string | yes |
| `password` | string | yes |

**Response `200`**

```json
{
  "success": true,
  "message": "Logged in successfully.",
  "data": {
    "user": {
      "id": 1,
      "name": "Alpha Events Customer",
      "email": "customer@eventflow.test",
      "role": {
        "id": 4,
        "name": "Customer",
        "slug": "customer"
      },
      "tenant": {
        "id": 1,
        "name": "Alpha Events",
        "slug": "alpha-events",
        "is_active": true
      }
    },
    "token": "1|plainTextToken..."
  }
}
```

**Errors:** `422` invalid credentials, `429` too many attempts.

---

#### `POST /api/auth/logout`

**Auth:** required

**Response `200`**

```json
{
  "success": true,
  "message": "Logged out successfully."
}
```

---

#### `GET /api/auth/me`

**Auth:** required

**Response `200`**

```json
{
  "success": true,
  "message": "Authenticated user retrieved.",
  "data": {
    "user": {
      "id": 1,
      "name": "Alpha Events Customer",
      "email": "customer@eventflow.test",
      "role": { "id": 4, "name": "Customer", "slug": "customer" },
      "tenant": { "id": 1, "name": "Alpha Events", "slug": "alpha-events", "is_active": true }
    }
  }
}
```

---

### Dashboard

#### `GET /api/dashboard`

**Auth:** required  
**Roles:** Tenant Admin, Manager

**Response `200`**

```json
{
  "success": true,
  "message": "Dashboard statistics retrieved.",
  "data": {
    "total_events": 5,
    "published_events": 3,
    "total_products": 10,
    "total_customers": 1,
    "total_orders": 1,
    "total_revenue": "22.00",
    "upcoming_events": [
      {
        "id": 1,
        "title": "Summer Gala",
        "location": "Austin",
        "start_date": "2026-10-01T18:00:00+00:00",
        "end_date": "2026-10-01T22:00:00+00:00",
        "status": "published",
        "created_by": { "id": 2, "name": "Alpha Events Manager" }
      }
    ],
    "recent_orders": [
      {
        "id": 1,
        "status": "confirmed",
        "total": "22.00",
        "user": { "id": 3, "name": "Alpha Events Customer" },
        "created_at": "2026-09-10T12:00:00+00:00"
      }
    ]
  }
}
```

`total_revenue` sums orders with status: `confirmed`, `processing`, `shipped`, `completed`.

---

### Events

#### `GET /api/events`

**Auth:** required

**Query parameters**

| Param | Type | Description |
| --- | --- | --- |
| `search` | string | Search title/location (max 100) |
| `status` | string | `draft`, `published`, `completed`, `cancelled` |
| `page` | integer | Page number (min 1) |
| `per_page` | integer | Items per page (1–50) |

Customers only see **published** events regardless of filter.

**Response `200`** — paginated list of event objects.

---

#### `POST /api/events`

**Auth:** required  
**Roles:** Tenant Admin, Manager

**Body**

| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| `title` | string | yes | max 255 |
| `description` | string | no | |
| `location` | string | yes | max 255 |
| `start_date` | datetime | yes | |
| `end_date` | datetime | yes | must be ≥ `start_date` |
| `capacity` | integer | yes | min 1 |
| `status` | string | yes | defaults to `draft` |

**Response `201`** — event object.

---

#### `GET /api/events/{id}`

**Auth:** required

Customers can only view **published** events. Managers/admins can view any status in their tenant.

**Response `200`** — event object.

```json
{
  "success": true,
  "message": "Event retrieved.",
  "data": {
    "id": 1,
    "title": "Summer Gala",
    "description": "Annual summer event.",
    "location": "Austin",
    "start_date": "2026-10-01T18:00:00+00:00",
    "end_date": "2026-10-01T22:00:00+00:00",
    "capacity": 100,
    "status": "published",
    "created_by": { "id": 2, "name": "Alpha Events Manager" },
    "created_at": "2026-09-01T10:00:00+00:00",
    "updated_at": "2026-09-01T10:00:00+00:00"
  }
}
```

---

#### `PUT /api/events/{id}`

**Auth:** required  
**Roles:** Tenant Admin, Manager

Same body fields as create (all required on update).

**Response `200`** — updated event object.

---

#### `DELETE /api/events/{id}`

**Auth:** required  
**Roles:** Tenant Admin, Manager

Soft-deletes the event.

**Response `200`**

```json
{
  "success": true,
  "message": "Event deleted successfully."
}
```

---

#### `POST /api/events/{id}/register`

**Auth:** required  
**Roles:** Customer only

Registers the authenticated customer for a **published** event.

**Response `201`**

```json
{
  "success": true,
  "message": "Registered for the event successfully.",
  "data": {
    "id": 1,
    "event_id": 1,
    "user_id": 3,
    "event": { "...": "event object" },
    "created_at": "2026-09-10T14:00:00+00:00"
  }
}
```

**Errors (`422`)**

| Condition | Message |
| --- | --- |
| Already registered | `You are already registered for this event.` |
| At capacity | `This event is at capacity.` |

---

### Products

#### `GET /api/products`

**Auth:** required

**Query parameters**

| Param | Type | Description |
| --- | --- | --- |
| `search` | string | Search name/SKU |
| `status` | string | `active`, `inactive` |
| `page` | integer | Page number |
| `per_page` | integer | Items per page (1–50) |

Customers only see **active** products.

**Response `200`** — paginated list of product objects.

---

#### `POST /api/products`

**Auth:** required  
**Roles:** Tenant Admin only

**Body**

| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| `name` | string | yes | max 255 |
| `description` | string | no | |
| `sku` | string | yes | unique per tenant, max 100 |
| `price` | number | yes | min 0 |
| `stock` | integer | yes | min 0 |
| `status` | string | no | `active` or `inactive`; defaults to `active` |

**Response `201`** — product object.

---

#### `GET /api/products/{id}`

**Auth:** required

**Response `200`**

```json
{
  "success": true,
  "message": "Product retrieved.",
  "data": {
    "id": 1,
    "name": "Event T-Shirt",
    "description": "Soft cotton tee.",
    "sku": "TEE-001",
    "price": "19.99",
    "stock": 50,
    "status": "active",
    "created_at": "2026-09-01T10:00:00+00:00",
    "updated_at": "2026-09-01T10:00:00+00:00"
  }
}
```

---

#### `PUT /api/products/{id}`

**Auth:** required  
**Roles:** Tenant Admin only

Same body fields as create.

**Response `200`** — updated product object.

---

#### `DELETE /api/products/{id}`

**Auth:** required  
**Roles:** Tenant Admin only

Soft-deletes the product.

**Response `200`**

```json
{
  "success": true,
  "message": "Product deleted successfully."
}
```

---

### Orders

#### `GET /api/orders`

**Auth:** required

**Query parameters:** `page`, `per_page` (1–50)

- **Customers** see only their own orders.
- **Managers / Tenant Admins** see all orders in the tenant.

**Response `200`** — paginated list of order objects.

---

#### `POST /api/orders`

**Auth:** required  
**Roles:** Customer only

Prices, tax, and totals are calculated **server-side**. Tax rate defaults to **10%** (`EVENTFLOW_TAX_RATE`).

**Body**

```json
{
  "items": [
    { "product_id": 1, "quantity": 2 }
  ]
}
```

| Field | Type | Required |
| --- | --- | --- |
| `items` | array | yes (min 1) |
| `items.*.product_id` | integer | yes |
| `items.*.quantity` | integer | yes (min 1) |

**Response `201`**

```json
{
  "success": true,
  "message": "Order created successfully.",
  "data": {
    "id": 1,
    "status": "confirmed",
    "subtotal": "20.00",
    "tax": "2.00",
    "total": "22.00",
    "payment_gateway": "fake",
    "payment_transaction_id": "fake_550e8400-e29b-41d4-a716-446655440000",
    "user": { "id": 3, "name": "...", "email": "...", "role": {}, "tenant": {} },
    "items": [
      {
        "id": 1,
        "product_id": 1,
        "quantity": 2,
        "price": "10.00",
        "total": "20.00",
        "product": { "...": "product object" }
      }
    ],
    "created_at": "2026-09-10T12:00:00+00:00",
    "updated_at": "2026-09-10T12:00:00+00:00"
  }
}
```

**Errors (`422`)**

| Condition | Field |
| --- | --- |
| Product not in tenant | `items` |
| Insufficient stock | `items.0` |
| Payment failure | `payment` |

On failure the entire order is rolled back (no stock decrement).

**Payment drivers** (`EVENTFLOW_PAYMENT_DRIVER`):

| Driver | Description |
| --- | --- |
| `fake` | Instant success (default for local/demo) |
| `razorpay` | Creates a Razorpay **test** order via API; requires `RAZORPAY_KEY_ID` and `RAZORPAY_KEY_SECRET` |

---

#### `GET /api/orders/{id}`

**Auth:** required

- **Customers** can view only their own orders.
- **Managers / Tenant Admins** can view any tenant order.

**Response `200`** — order object (same shape as create response).

---

## Enums

### Event status

`draft` · `published` · `completed` · `cancelled`

### Product status

`active` · `inactive`

### Order status

`pending` · `confirmed` · `processing` · `shipped` · `completed` · `cancelled`

New orders are created as `confirmed` after successful payment.

### User roles

`super_admin` · `tenant_admin` · `manager` · `customer`

---

## Quick start (cURL)

```bash
# Health check
curl -s http://localhost/api/health | jq

# Login
TOKEN=$(curl -s -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"customer@eventflow.test","password":"password"}' \
  | jq -r '.data.token')

# List published events
curl -s http://localhost/api/events \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq

# Place an order
curl -s -X POST http://localhost/api/orders \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"items":[{"product_id":1,"quantity":1}]}' | jq
```

---

## Notes

- Dates are returned in **ISO 8601** format.
- Monetary values are returned as strings with two decimal places.
- Passwords and tokens are never included in user resource responses.
- Background jobs dispatch notifications for order creation, order status changes, and event registrations (database + log channels).
