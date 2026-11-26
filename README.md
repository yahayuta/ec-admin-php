# ec-admin-php

Order and inventory admin screens for a fictional multi-channel e-commerce shop, built with **Laravel 12 + MySQL**.

The screens are deliberately plain. The point of the repository is the part that breaks in real admin tools: what happens to stock when staff allocate, ship and cancel orders, and how to keep every stock change explainable.

All data is fictional (made-up SKUs, order numbers and customer names).

## What it does

| Screen | |
|---|---|
| Orders | List with filters (status, sales channel, order number), order detail with line items |
| Order actions | Allocate stock → ship, or cancel. Only the buttons allowed for the current status are shown |
| Inventory | Stock list with a "below reorder point" filter, per-product stock history |
| Stock adjustment | Manual +/- correction with a required reason (stocktaking, damaged goods) |

## Design decisions

**Status transitions live in one place.** `App\Enums\OrderStatus` defines the allowed moves:

```
new ──> allocated ──> shipped
 │          │
 └──────────┴──> cancelled
```

The service refuses anything else (`InvalidStatusTransitionException`), so "ship an unallocated order" or "cancel a shipped order" cannot happen from any screen or future API.

**Allocation is all or nothing.** `App\Services\OrderWorkflow::allocate()` adds up quantities per product first (the same SKU can appear on several lines), checks every SKU, and only then takes stock. If anything is short, nothing is reserved and the message lists every short SKU, not just the first one.

**Concurrent clicks are safe.** Each action runs in a transaction, locks the order row, then locks the product rows **in id order**. Two staff pressing "allocate" on the same order cannot both succeed, and two orders that share products wait for each other instead of deadlocking.

**Every stock change has a ledger row.** `stock_movements` records the change, the stock after it, the reason (allocation / cancellation / manual adjustment), the order and a note. The current stock can always be explained from its history. `stock` is an unsigned column, so MySQL itself refuses negative stock even if application code had a bug.

**Business errors are messages, not 500s.** Short stock or an invalid status change returns to the screen with a message the operator can act on.

**Lazy loading is an error in development.** `Model::preventLazyLoading()` catches N+1 queries before they reach production.

## Run locally

Requirements: PHP 8.2+, Composer, Docker (for MySQL).

```bash
composer install
cp .env.example .env
php artisan key:generate
docker compose up -d          # MySQL 8.4 on localhost:3306
php artisan migrate --seed    # 30 products, 80 fictional orders
php artisan serve             # http://localhost:8000
```

There is no login screen. In production an admin tool like this belongs behind the company's SSO (for example an identity-aware proxy in front of the app) rather than its own password table.

## Tests

```bash
vendor/bin/phpunit            # SQLite in memory by default
```

20 tests cover the status rules, all-or-nothing allocation, stock returned on cancellation, the ledger rows, filters and the stock adjustment rules. CI runs the same tests against MySQL 8.4, plus migrate / seed / rollback, on every push.

## Structure

```
app/Enums/OrderStatus.php          allowed status transitions
app/Services/OrderWorkflow.php      allocate / ship / cancel with row locks
app/Services/StockAdjuster.php      manual corrections with a reason
app/Http/Controllers/               thin controllers: validate, call a service, redirect
database/migrations/                products, orders, order_items, stock_movements
tests/Feature/                      HTTP-level tests for each rule
```

Built together with an AI coding assistant (Claude).

## License

MIT
