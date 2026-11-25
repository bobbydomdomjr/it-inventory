# Multi-office rollout — Stages 1 and 2

## Stage 1: ownership schema

- Adds the `offices` master table and `App\Models\Office` model.
- Adds nullable `office_id` foreign keys to primary office-owned tables.
- Keeps existing rows unassigned until an administrator supplies a verified ownership mapping.

## Stage 2: office administration and Eloquent-level isolation

- Adds Office Administration routes and screens for creating, viewing, updating, deactivating, and safely archiving offices.
- Adds `offices.view` and `offices.manage` permissions. The migration grants them to existing `super-admin` and `system-admin` roles. The seeder also defines these permissions without granting office administration to ordinary office users.
- Adds an Eloquent `BelongsToOffice` concern to primary office-owned models and workflow child models. Non-Super-Admin users are restricted to records with their `office_id`; users without an office fail closed for those model queries.
- New records created by non-Super-Admin users inherit the authenticated user's office. Updates cannot change a record's office.
- Model-backed dropdown references in generic inventory CRUD are rechecked through scoped Eloquent queries because Laravel's `exists` validation rule checks the database table directly and bypasses Eloquent scopes.
- Adds office ownership to purchase, receiving, stock-count, audit-item, maintenance schedule, license, and ticket-comment child records. Bulk-created stock-count and audit-item rows explicitly inherit the parent office.
- Adds the office administration link and office column to user administration. Super Admin can assign an office when creating an account; ordinary administrators cannot assign accounts outside their own office.

## Critical rollout requirement: map existing data before using tenant accounts

The ownership columns remain nullable by design. Do not guess the ownership of existing data. Before enabling the application for ordinary users in multiple offices:

1. Back up the database and test the migrations on a restored copy.
2. Create all real offices with stable office codes.
3. Prepare an approved mapping for every existing user, department, location, category, supplier, employee, asset, stock item, license, document, and workflow record.
4. Backfill the appropriate `office_id` values using that mapping. Do not assign all records to one office unless that is the approved ownership model.
5. Verify that each asset, employee, department, location, supplier, document, purchase request, purchase order, receiving report, assignment, and workflow child belongs to a consistent office.
6. Confirm each non-Super-Admin account has an active office assignment.
7. Run direct-ID and cross-office tests with each role before production rollout.

## Security boundary and remaining work

Stage 2 applies office filtering through Eloquent global scopes. This is an important enforcement layer, but it is **not yet a certification of complete tenant isolation**. Direct query-builder / `DB::table()` access, raw SQL, exports, reports, import paths, background jobs, notifications, approval queues, and any workflow-specific cross-record logic must still be reviewed and tested. Append-only ledgers and organization-wide audit records require explicit ownership rules rather than blind global scopes. Do not claim the application is fully multi-office secure until those paths have been audited and tested.

Super Admin is organization-wide. Other users are restricted to their assigned office for models using `BelongsToOffice`. A record with `office_id = NULL` is intentionally invisible to non-Super-Admin Eloquent queries; it must be mapped rather than silently assigned.

## Local application and verification

Back up the database first. From the project directory:

```bash
php artisan optimize:clear
php artisan migrate:status
php artisan migrate --pretend
php artisan migrate
php artisan route:list --name=admin.offices
php artisan test
php artisan inventory:doctor
```

Review the generated SQL before applying it to production. The new migrations add nullable ownership columns and do not backfill existing records.

## Validation status of this package

All changed PHP source files were checked with `php -l`. The Laravel runtime, migrations, browser UI, and MySQL integration tests were not executed in this packaging environment. Run the commands above in the user's Laragon project and resolve any reported failures before deployment.
