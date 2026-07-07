# KAWI POS Foundation

## Direction

KAWI POS is currently Laravel + Filament first. Vue/Nuxt is paused.

## Tenancy

- `businesses` stores companies/business units.
- `branches` represents outlets.
- Users are attached to allowed businesses through `business_user`.
- Users are attached to allowed outlets through `outlet_user`.
- Active context is stored in session:
  - `active_business_id`
  - `active_outlet_id`

## RBAC

Spatie Laravel Permission is used with KAWI custom Role and Permission models.

Seeded roles:

- owner
- admin
- cashier
- warehouse
- accounting

Seeded permissions:

- manage business
- manage outlet
- manage product
- manage inventory
- manage sales
- view report
- manage expense
- manage user

## Filament

Active baseline resources:

- Businesses
- Branches
- Chart of Accounts
- Outlet Account Mappings
- Journal Entries

## Accounting

- Accounts are scoped per business.
- Outlet is an accounting dimension.
- Journal entries carry `business_id` and `outlet_id`.
- Journal entry lines carry `account_id`, `debit`, and `credit`.
- Opening balance is a posted journal entry with `source_type = opening_balance`.
