# Database Review and Repair — it_inventory

Review basis: uploaded `it_inventory.sql` and the matching Laravel source package. This review inspects the supplied SQL dump; it does not connect to or modify the live MySQL server.

## Confirmed findings from the supplied SQL dump

1. The dump contains **55 tables**.
2. The `migrations` table contains **18 migration records**, matching the 18 migrations in the previously supplied project package before this repair migration is added.
3. The tables and columns queried directly by the dashboard are present:
   - `assets`: `status`, `purchase_cost`, `warranty_expiration`, `created_at`, `category_id`
   - `support_tickets`: `status`
   - `inventory_items`: `quantity_on_hand`, `reorder_level`, `name`, `unit`
   - `software_licenses`: `software_name`, `expires_at`
   - `licenses`: `expiry_date`, `software_id`
   - `maintenance_records`: `scheduled_at`, `status`
   - `approvals`: `status`, `approver_id`
4. The dump has an `offices` table but **zero office rows**.
5. Every dumped `office_id` value on rows present in the following tables is `NULL`: `assets`, `asset_categories`, `asset_disposals`, `assignments`, `departments`, `employees`, `inventory_items`, `locations`, `maintenance_records`, `maintenance_schedules`, `software_licenses`, `stock_counts`, `stock_count_items`, `stock_transactions`, `suppliers`, `support_tickets`, and `users`.
6. `admin@inventory.local` exists, is active, and is attached to the `super-admin` role. The SQL audit log records successful login events, including one at 2026-11-24 01:56:42. No password hashes or credentials are reproduced in this report.

## Why the office setup matters

The project applies a global office scope to office-owned models. A non-super-admin account without an `office_id` is deliberately restricted to an empty result set, and creation is rejected with an "account is not assigned to an office" validation message. Because the dump has no office rows and every user/record `office_id` is NULL, regular inventory roles cannot see the current inventory or create office-owned records.

This is a confirmed database configuration defect. It is **not sufficient evidence by itself** to explain the administrator's `/dashboard` HTTP 500: the administrator is assigned the super-admin role, and the dashboard's required tables and columns are present in the dump. The exact 500 exception must still be checked in the deployed `storage/logs/laravel.log`.

## Repair included in this package

A new additive migration, `2026_11_24_000001_create_default_office_and_assign_existing_records`, does the following:

- Creates a default office with code `MAIN` and name `Main Office`, if it does not already exist.
- Reactivates that office if it was soft-deleted.
- Assigns only rows whose `office_id` is NULL to that office across all office-owned tables.
- Preserves every non-NULL office assignment and all business data.
- Has a deliberately non-destructive `down()` method; it does not clear ownership on rollback.

The `DatabaseSeeder` now initializes the same default office and associates the sample records with it on fresh installations. The `BelongsToOffice` trait now assigns new records created by a super administrator to their own office, or the first active office, unless the administrator explicitly selected an office.

## Deployment instructions

### Preferred method: run the Laravel migration

1. Back up the database in phpMyAdmin or HeidiSQL.
2. Deploy this updated project code, preserving the existing `.env`.
3. In Laragon Terminal, from the project directory, run:

```bash
php artisan migrate
php artisan optimize:clear
php artisan inventory:doctor
```

4. Verify that the `offices` table contains `MAIN` and that the previously unassigned rows now have `office_id`.
5. Test the dashboard as Super Admin, then test inventory visibility using a non-super-admin account.

### Alternative: apply the SQL-only repair

Use `it_inventory_office_ownership_repair.sql` against the existing `it_inventory` database after taking a backup. It inserts/reactivates `MAIN` and updates only `NULL office_id` values. Do not apply both the SQL patch and migration as separate repairs unless desired; they are idempotent with respect to existing non-NULL assignments, but the migration will be recorded only when run through Laravel.

## Dashboard HTTP 500

The SQL dump confirms that the dashboard's directly queried tables and columns exist. The administrator's successful authentication is also recorded. The database dump does not contain the runtime exception thrown when `/dashboard` is rendered. If the 500 continues after the office migration, capture the exact latest exception with:

```powershell
Get-Content .\storage\logs\laravel.log -Tail 120
```

Do not run `php artisan migrate:fresh`; it drops tables and can destroy inventory records. Do not share `.env`, database passwords, or password hashes.

## Verification limits

- PHP syntax checks passed for the new migration, modified seeder, and modified office-scope trait.
- Laravel route registration still succeeds with 125 registered routes and no unresolved controller action references.
- This environment does not have a PDO MySQL driver or a live connection to the user's Laragon server. The SQL migration and data updates therefore have not been executed against the submitted database in this environment. Run them after taking a database backup.
