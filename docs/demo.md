# EventFlow — Live Demo Guide

Use this guide to explore the deployed EventFlow application.

## Live URLs

| Resource | URL |
| --- | --- |
| **Customer login** | https://multitenet-production-nbyekk.laravel.cloud/ |
| **Customer portal** | https://multitenet-production-nbyekk.laravel.cloud/portal |
| **Admin panel** | https://multitenet-production-nbyekk.laravel.cloud/admin |
| **API health** | https://multitenet-production-nbyekk.laravel.cloud/api/health |
| **API base** | https://multitenet-production-nbyekk.laravel.cloud/api |
| **GitHub** | https://github.com/kanhaiyathakur1941/multitenet |

---

## Demo logins

All accounts use password: **`password`**

| Email | Role | What to try |
| --- | --- | --- |
| `admin@alpha.eventflow.test` | Tenant Admin | Events, products, orders, users |
| `manager@alpha.eventflow.test` | Manager | Events, view products, orders |
| `superadmin@eventflow.test` | Super Admin | All tenants |
| `customer@eventflow.test` | Customer | Customer portal — events, shop, cart, orders |

---

## Quick walkthrough (5 minutes)

### 1. Customer portal (Blade)

1. Open https://multitenet-production-nbyekk.laravel.cloud/
2. Log in as `customer@eventflow.test` / `password`
3. Browse **Events**, register for an event, shop products, add to cart, and place an order

### 2. Admin panel

1. Open https://multitenet-production-nbyekk.laravel.cloud/admin
2. Log in as `admin@alpha.eventflow.test` / `password`
3. Browse **Events**, **Products**, and **Orders**

### 3. API health check

```bash
curl https://multitenet-production-nbyekk.laravel.cloud/api/health
```

Expected:

```json
{"success":true,"message":"EventFlow API is running.","data":{"status":"ok"}}
```

### 4. API login and place an order

```bash
# Login
curl -s -X POST https://multitenet-production-nbyekk.laravel.cloud/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"customer@eventflow.test","password":"password"}'
```

Copy the `token` from the response, then:

```bash
# List products (replace TOKEN)
curl -s https://multitenet-production-nbyekk.laravel.cloud/api/products \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json"

# Create order (replace TOKEN and PRODUCT_ID)
curl -s -X POST https://multitenet-production-nbyekk.laravel.cloud/api/orders \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"items":[{"product_id":1,"quantity":1}]}'
```

Full API reference: [api.md](api.md)

---

## Payments (Razorpay test mode)

By default the live demo uses the **fake** payment gateway (instant success).

To enable **Razorpay test mode** on Laravel Cloud:

1. Create a [Razorpay test account](https://dashboard.razorpay.com/) and copy **Key ID** + **Key Secret** (Test Mode).
2. In Laravel Cloud → **Environment variables**, add:

| Variable | Value |
| --- | --- |
| `EVENTFLOW_PAYMENT_DRIVER` | `razorpay` |
| `RAZORPAY_KEY_ID` | `rzp_test_...` |
| `RAZORPAY_KEY_SECRET` | your test secret |
| `RAZORPAY_CURRENCY` | `INR` |

3. Redeploy.

### Customer portal checkout

1. Sign in at `/` as `customer@eventflow.test` / `password` (redirects to `/portal` after login).
2. Add products to the cart and click **Pay with Razorpay**.
3. Complete payment in the Razorpay modal using a [test card](https://razorpay.com/docs/payments/payments/test-card-upi-details/) (e.g. `4111 1111 1111 1111`, any future expiry, any CVV).
4. After success you are redirected to the order page with `payment_transaction_id` set.

If you close the Razorpay modal or click **Cancel payment**, the pending order is cancelled and product stock is restored.

Amounts are charged in `RAZORPAY_CURRENCY` (default INR). Product prices in the demo are treated as that currency when Razorpay is enabled.

The REST API still uses the **fake** driver for instant orders. With `EVENTFLOW_PAYMENT_DRIVER=razorpay`, API order creation returns a validation error — use the portal checkout instead.

---

## Email notifications

When a customer places an order or registers for an event, EventFlow notifies the **customer account** (the email on that user — not the admin).

Notifications are stored/sent via three channels:

| Channel | Where to check |
| --- | --- |
| **Database** | `notifications` table (always saved when the queue job runs) |
| **Log** | Laravel Cloud → **Logs** (search for `Order #` or `registered for event`) |
| **Email** | Customer’s inbox — only if SMTP is configured and a queue worker is running |

> Demo customer `customer@eventflow.test` is not a real mailbox. For a live email test, temporarily set that user’s email to a real address in the admin panel or via tinker.

### Laravel Cloud mail setup

Add these environment variables (example with [Mailtrap](https://mailtrap.io/) or any SMTP provider):

| Variable | Example |
| --- | --- |
| `EVENTFLOW_MAIL_NOTIFICATIONS` | `true` |
| `MAIL_MAILER` | `smtp` |
| `MAIL_HOST` | `sandbox.smtp.mailtrap.io` |
| `MAIL_PORT` | `2525` |
| `MAIL_USERNAME` | your SMTP username |
| `MAIL_PASSWORD` | your SMTP password |
| `MAIL_FROM_ADDRESS` | `noreply@eventflow.test` |
| `MAIL_FROM_NAME` | `EventFlow` |

Redeploy after changing variables.

> **Deploy tip:** Use `php artisan migrate --force` for routine deploys. Only use `--seed` on the very first deploy when the database is empty.

> **Note:** Notifications are queued. You must run a queue worker (see below) for emails to send.

---

## Queue worker on Laravel Cloud

Background jobs (emails, notifications) require a **queue worker**. Without it, jobs sit in the `jobs` table and never run.

### Where to add it (step by step)

1. Go to [cloud.laravel.com](https://cloud.laravel.com) and open your application.
2. Open the **production** environment.
3. On the **infrastructure canvas** (the diagram showing App + Database), click your **App** cluster card.
4. In the cluster panel, find **Background processes** (or **Processes**).
5. Click **Add background process** (or **+ Add process**).
6. Enter this command:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

7. Set size to **Flex 512MB** (enough for demo).
8. Click **Save**, then **Deploy** the environment.

### Verify the worker is running

After deploy, place a test order via API or register for an event. Check:

- Laravel Cloud → **Logs** for notification log entries
- Your mail inbox (if SMTP is configured)
- Database `notifications` table (via Cloud **Commands** → `php artisan tinker` if needed)

### Local development

Run alongside the app:

```bash
./bin/php artisan queue:work
```

---

## Multi-tenancy demo

| Tenant | Admin login |
| --- | --- |
| Alpha Events | `admin@alpha.eventflow.test` |
| Beta Gatherings | `admin@beta.eventflow.test` |

Each tenant only sees its own events, products, and orders. Cross-tenant access returns **404**.

---

## 5-minute review for HR / interviewer

**What it is:** Multi-tenant SaaS demo — one Laravel app, multiple organizations (tenants), each with isolated events, products, and orders.

**Try this (≈5 min):**

1. **Customer experience** — https://multitenet-production-nbyekk.laravel.cloud/  
   Login: `customer@eventflow.test` / `password` → browse events, add products to cart, place an order.

2. **Admin experience** — https://multitenet-production-nbyekk.laravel.cloud/admin  
   Login: `admin@alpha.eventflow.test` / `password` → view/update orders, manage events and products.

3. **API** — https://multitenet-production-nbyekk.laravel.cloud/api/health  
   Returns JSON confirming the API is live.

4. **Code** — https://github.com/kanhaiyathakur1941/multitenet  
   Laravel 12, Pest tests, Filament admin, Sanctum API, queue jobs, Razorpay test payments.

**Highlights:** tenant isolation, role-based access, queued notifications, payment gateway abstraction (fake + Razorpay), deployed on Laravel Cloud.

---

## Support

- API docs: [docs/api.md](api.md)
- Project README: [README.md](../README.md)
