# Gawdee commerce platform

A modern PHP ecommerce storefront with customer accounts and order tracking, a secure admin panel, SQLite-backed products and orders, homepage CMS and banner uploads, Razorpay checkout verification, a merchant-configurable DTDC adapter, Groq/OpenAI AI chat, and AI-assisted blog publishing.

The commerce control centre includes assisted phone/counter order creation, searchable order operations, valid-state fulfilment controls, private team notes, CSV exports, printable invoices and packing slips, customer history, low-stock alerts, live inventory valuation and an auditable stock movement ledger.

## Run locally

```bash
php -S localhost:8080 router.php
```

Then open `http://localhost:8080`. The router blocks private database and server include files when using PHP's development server.

## First admin setup

Open `http://localhost:8080/admin/login.php`. On the first visit, create the administrator account; the project does not ship a default password.

Configure services in the admin panel:

- **Integrations**: Razorpay Key ID, encrypted Key Secret, webhook secret, and the webhook URL shown on screen.
- **DTDC**: the booking endpoint, authentication scheme, credentials, and payload schema issued for your DTDC merchant account. A custom JSON template supports merchant-specific contracts.
- **AI Studio**: select Groq or OpenAI, save its encrypted API key and model, enable homepage chat, and optionally configure auto-blog publishing.

External integrations stay inactive until credentials are saved. Cash on delivery remains available by default for local end-to-end checkout testing.

Orders are committed to the local fulfilment queue before online payment initialization, so a gateway or courier outage cannot make a checkout disappear. Checkout tokens prevent duplicate submissions, inventory is reserved/released idempotently, and incomplete Razorpay orders remain visible as attention items. DTDC can be switched off independently; the Orders screen then provides the complete manual packing, dispatch, tracking and delivery workflow.

## Customer accounts

Customers can register or sign in before checkout. Orders placed while signed in are linked to the account and appear in **My account**, where the customer can review order details, payment status, fulfilment progress, courier events and the DTDC tracking link. Customer addresses are saved as checkout defaults and can be updated from the profile screen.

Guest checkout remains available. The order-success screen offers the guest a safe way to create an account and attach only the order just placed in that browser session.

## Catalogue import

The current official Gawdee catalogue and product-story images are stored locally. To refresh the catalogue from the live Gawdee product service, run:

```bash
php scripts/import-gawdee-catalog.php
```

The importer upserts products by source ID, preserves existing local identifiers where possible, downloads gallery and editorial images into `assets/images/catalog/`, and leaves customer, order and CMS data untouched.

## Auto-blog schedule

Set a long scheduler token in **Admin > AI studio**, enable scheduled auto-blog, then call:

```text
http://localhost:8080/cron/auto-blog.php?token=YOUR_TOKEN
```

Run that URL daily with cron or Windows Task Scheduler. The endpoint only publishes when the configured frequency is due.

## Structure

- `index.php` — CMS-powered storefront homepage
- `products.php` — searchable and category-filtered complete catalogue
- `product.php` — official product detail, gallery, variants and product-story content
- `login.php`, `register.php` and `account.php` — customer identity, profile and order history
- `account-order.php` — secure owned-order details, fulfilment timeline and courier tracking
- `checkout.php` — server-priced COD and Razorpay checkout
- `blog.php` and `blog-post.php` — public journal
- `admin/` — secure login, CMS, commerce operations and integrations
- `admin/export-orders.php` — filtered order CSV export for fulfilment and accounting
- `admin/order-print.php` — printable order invoice and price-free packing slip
- `api/` — checkout, payment verification, webhooks, AI chat and newsletter endpoints
- `cron/auto-blog.php` — token-protected AI publishing endpoint
- `scripts/import-gawdee-catalog.php` — non-destructive official catalogue synchronizer
- `includes/platform.php` — SQLite schema, authentication, CMS and encrypted settings
- `includes/integrations.php` — Razorpay, DTDC, Groq and OpenAI service clients
- `includes/data.php` — SQLite catalogue and transformed official product details
- `assets/css/style.css` — responsive storefront, catalogue, account, checkout, journal and chat UI
- `assets/js/app.js` — storefront interactions, filtering, cart, product UI and AI chat

## Production notes

Serve the site over HTTPS, keep `storage/` outside the public document root when possible, set `GAWDEE_APP_KEY` in the environment, enter live credentials only on the production server, and configure Razorpay webhooks before fulfilment.

## Smoke test

```bash
php tests/smoke.php
php tests/commerce.php
```

The smoke suite checks the seeded catalogue and CMS, encrypted secret storage, Razorpay signature logic, DTDC payload mapping, and AI blog HTML sanitization. The commerce suite verifies COD receipt, server-side offers, duplicate-submit protection, inventory deduction/restock, manual dispatch with DTDC off, delivery/COD collection, payment-failure retention, and admin order visibility without calling paid external APIs.
