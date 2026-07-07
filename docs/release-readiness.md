# KAWI POS Release Readiness

Status: baseline reset ready.

## Current Direction

- Laravel + Filament first.
- Vue/Nuxt paused.
- Multi-business and multi-outlet context is required after login.
- Branch represents outlet.
- COA/accounts are scoped per business.
- Outlet is an accounting dimension.

## Local Run

```bash
composer install
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Filament:

```text
http://127.0.0.1:8000/admin
```

Login:

```text
Email: owner@kawipos.local
Password: password
```

## Active Baseline

- Businesses
- Branches/outlets
- Spatie RBAC seeders
- Default owner user
- Active business/outlet context API
- Filament active context page
- Accounting resources:
  - Chart of Accounts
  - Outlet Account Mappings
  - Journal Entries

## Verification

```bash
php artisan test
vendor/bin/pint
```

## Not In Baseline Yet

- Product/category
- Sales/POS transaction
- Inventory
- Recipe
- Purchasing
- Operational expense
- Vue/Nuxt frontend
