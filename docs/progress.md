# KAWI POS Progress Log

## Current Baseline

Status: active.

- Project direction is Laravel + Filament first.
- Vue/Nuxt frontend development is paused.
- Branch represents outlet.
- Active business and outlet context are required in Filament after login.
- COA/accounts are scoped per business.
- Outlet is used as an accounting dimension.

## Active Modules

- Business CRUD.
- Branch/outlet CRUD.
- Spatie RBAC seeders and default owner user.
- ActiveContext service and API.
- Filament active business/outlet context page.
- Unit Master.
- Accounting:
  - Chart of Accounts.
  - Outlet Account Mappings.
  - Journal Entries.

## Stage 77 - Initial Master Data Seeders

Status: complete.

- Created RolePermissionSeeder, BusinessSeeder, OutletSeeder, and DefaultUserSeeder.
- Seeded Kawi Chinese Food, Warung Guan, and Lumpia Busung Yeh.
- Seeded one active outlet for each business.
- Seeded owner, admin, cashier, warehouse, and accounting roles.
- Seeded default owner user `owner@kawipos.local`.
- Attached owner user to all seeded businesses and outlets.

## Stage 78 - Active Business and Outlet Context

Status: complete.

- Added active context API:
  - `GET /api/me/context-options`
  - `POST /api/me/active-context`
  - `GET /api/me/active-context`
  - `DELETE /api/me/active-context`
- Added active business and active outlet middleware.
- Added ActiveContext service.

## Stage 79 - Filament Active Business and Outlet Context

Status: complete.

- Added Filament page for active business/outlet selection.
- Added Filament middleware to enforce valid active context.
- Added navigation item Active Business / Outlet.
- Updated TenantContext to read active session context.

## Stage 80 - Modern Accounting Foundation

Status: complete.

- Added modern accounting foundation.
- COA/accounts are scoped per business.
- Journal entries carry business and outlet context.
- Journal entry lines carry account, debit, and credit.
- Added outlet account mapping.
- Added Filament resources:
  - AccountResource.
  - OutletAccountMappingResource.
  - JournalEntryResource.
- Added AccountSeeder for baseline COA and outlet mappings.
- Added tests for accounting foundation.

## Conservative Legacy Cleanup

Status: complete.

- Removed obsolete Filament resources for product/category, POS setup, inventory, purchasing, production, suppliers, taxes, and warehouse.
- Removed legacy demo seeders from the active DatabaseSeeder flow.
- Removed legacy demo seeders that populated old product, inventory, POS, purchasing, accounting expense, and offline sample data.
- Removed legacy tests that depended on old demo modules.
- Removed obsolete stage docs for paused Vue/Nuxt, POS, inventory, purchasing, product/category, and frontend API work.
- Updated Help and release docs to reflect the current Filament-first baseline.
- Kept current business, branch/outlet, RBAC, active context, audit, user access, and Stage 80 accounting foundation.
- Deep runtime/schema pruning for legacy controllers, services, models, migrations, and Vue assets is deferred to a dedicated pass because it can affect existing databases and composer/npm build wiring.

## Stage 81 - Unit Master

Status: complete.

- Added business-scoped `units` table with soft deletes.
- Added Unit model.
- Added Filament Master Data > Units resource.
- Unit forms auto-fill `business_id` from active business context.
- Unit listing is scoped to active business.
- Added UnitSeeder for Porsi, Pcs, Gram, Kilogram, Mililiter, and Liter per business.
- DatabaseSeeder now calls UnitSeeder after OutletSeeder.
- Added Unit Master tests for seeding, active business scoping, context auto-fill, missing context redirect, and required name.

## RBAC Demo Users

Status: complete.

- Added RbacDemoUserSeeder for demo admin, cashier, warehouse, and accounting users.
- Demo users are scoped to their assigned business and outlet through `business_user` and `outlet_user`.
- Demo users receive the matching Spatie role.
- DatabaseSeeder calls RbacDemoUserSeeder after DefaultUserSeeder.
- Added tests for demo user creation, role assignment, business/outlet scoping, owner access, and active context options.
- Filament panel access now allows valid KAWI POS RBAC roles and rejects users without a role.

## Filament Header Context Selector

Status: complete.

- Added compact business/outlet selector to the Filament topbar near the user menu.
- Header selector validates business and outlet access before updating session context.
- Changing business clears the previous outlet and auto-selects the outlet when only one is available.
- Filament middleware now auto-selects context for users with exactly one accessible business and outlet.
- Users with multiple available contexts still use manual selection through the header or Active Business / Outlet page.
- Active Business / Outlet page remains as fallback/manual context page.

## Not In Baseline Yet

- Product/category.
- Sales/POS transaction.
- Inventory.
- Recipe.
- Purchasing.
- Operational expense.
- Vue/Nuxt frontend.

## Tracking Backward

Each stage is committed separately. Use:

```bash
git log --oneline
git show <commit>
```
