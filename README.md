# IT Inventory Management System

A Laravel 13 application for managing an organization's IT assets, custody, consumables, software entitlements, procurement, maintenance, support tickets, physical inventory, approvals, documents, and system accountability. The interface uses a restrained navy-and-white administrative design, responsive navigation, compact registers, searchable filters, and clear status indicators.

> **Deployment status:** This is an active development release with working core workflows and a broader normalized schema. It is not yet a production-complete implementation of all 24 required modules. Review `docs/IMPLEMENTATION_STATUS.md` and complete the MySQL/Laragon acceptance checks before entering official records.

## Quick start (Windows / Laragon)

1. Extract this folder to `C:\\laragon\\www\\it-inventory`.
2. Copy `.env.example` to `.env` and set the MySQL database credentials and `APP_URL`.
3. Start Apache and MySQL in Laragon, create the configured database, then run `composer install --no-interaction --prefer-dist`.
4. Enable the required PHP extensions listed in the setup section below; in particular `pdo_mysql`, `dom`, `mbstring`, `xml`, `xmlwriter`, `zip`, `curl`, and `gd`.
5. Run `php artisan key:generate` if the new `.env` has no key, then `php artisan migrate --seed` on a clean test database.
6. Run `START_LARAGON.bat` or `php artisan serve --host=127.0.0.1 --port=8080`.
7. Open `http://127.0.0.1:8080/login` in Chrome or Edge. Do not open PHP/Blade files directly using `file://`.

See `docs/REPAIR_AND_QA_REPORT.md` for the browser diagnosis, verified checks, remaining requirements gaps, and acceptance steps. See `docs/DATABASE_REVIEW_AND_REPAIR.md` for the confirmed office-ownership database issue and its non-destructive migration.

## Technology requirements

- PHP 8.3 or later
- Laravel 13
- MySQL 8.0+ (MariaDB compatibility should be validated on the target server)
- Composer 2
- Node.js 20.19+ or 22.12+ and npm (for the Vite production build)
- PHP extensions required by Laravel and the report/export packages, including `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd`, `hash`, `iconv`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `session`, `simplexml`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter`, `zip`, and `zlib`

## Windows / Laragon installation

1. Back up your existing `C:\laragon\www\it-inventory` directory. Extract this project into that directory. This source package intentionally does not include `.env`, `vendor`, or `node_modules`. It includes the prebuilt Vite production assets so the application can render before you reinstall frontend tooling.
2. Preserve your existing `.env`. If you do not have one, copy the template:

   ```powershell
   Copy-Item .env.example .env
   ```

3. Create a MySQL database named `it_inventory` in phpMyAdmin or HeidiSQL. Update the database connection in `.env`:

   ```dotenv
   APP_NAME="IT Inventory Management"
   APP_ENV=local
   APP_KEY=
   APP_DEBUG=true
   APP_URL=http://127.0.0.1:8080
   APP_TIMEZONE=Asia/Manila

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=it_inventory
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. In Laragon Terminal, install the exact locked Composer dependencies and generate the application key:

   ```bash
   cd /c/laragon/www/it-inventory
   composer install --no-interaction --prefer-dist
   php artisan key:generate
   ```

5. The package includes a prebuilt Vite production bundle. Install frontend dependencies and rebuild only when you are changing frontend source files:

   ```bash
   npm ci
   npm run build
   ```

   The package lockfile is committed. If `npm ci` reports a lockfile mismatch, stop and review the dependency change instead of silently replacing the lockfile on a production server.

6. Run database migrations and seed initial roles, permissions, and reference records:

   ```bash
   php artisan migrate --seed
   php artisan optimize:clear
   php artisan inventory:doctor
   ```

   **Do not use `migrate:fresh` on a database containing records.** The new version includes incremental migrations for procurement requesters, goods-receipt controls, and procurement-to-asset links.

7. Start the application:

   ```bash
   php artisan serve --host=127.0.0.1 --port=8080
   ```

8. Open `http://127.0.0.1:8080`.

### Initial administrator

- Email: `admin@inventory.local`
- Password: `ChangeMe123!`

Change this password immediately after the first successful sign-in. Do not use the seeded account or `APP_DEBUG=true` in a production deployment.

## Included modules and working workflows

### Overview and administration

- Dashboard summaries for asset counts and status (including retired assets), total acquisition value, total and expiring software licenses across both license registers, low-stock items, and open tickets
- Authentication, login throttling, persistent account lockout, login history, forced password rotation, TOTP-based MFA enrollment and sign-in challenge, active-account checks, logout/session invalidation
- Role-based permissions, seeded role profiles, user activation/deactivation, role-permission administration
- Organization/system settings, timezone, currency, reminder thresholds, automatic-backup toggle and retention policy
- In-app notifications with email delivery through Laravel's mail channel when SMTP is configured
- Activity/audit trail with actor, action, record reference, IP address, user agent, and before/after values where recorded

### Asset register and accountability

- Asset registration, editing, archiving, categories, tags, serial numbers, technical specifications, purchase values, warranties, suppliers, departments, rooms/locations, condition and status
- Asset search, filters, pagination, detail timeline, CSV export and CSV import template
- QR and Code 128 barcode label rendering from asset details; browser scanning uses BarcodeDetector where supported, with manual identifier lookup as fallback
- Asset assignment, recipient acknowledgment, optional drawn signature, printable custody receipt, return and return-condition recording
- Employee-to-employee transfer request, approval/rejection, and custodian update
- Disposal request, configured sequential/parallel approval flow when enabled (or manual sequential fallback), completion, retirement state, and salvage value
- Procurement request, sequential approval, purchase-order recording, partial/full receiving and inspection, then conversion of accepted units into asset records

### Stock, software and services

- Consumable item register, opening balance transaction, stock-in, stock-out, balance adjustment, append-only stock movement ledger, physical stock count schema, and low-stock alerts
- Software product catalog, normalized license entitlements, encrypted license keys, seat allocation to employees/assets, revocation, renewal history, expiry monitoring and available-seat counts
- Maintenance work orders, preventive/corrective type, priority, technician/provider, close/cancel workflow, parts/cost/resolution, and next-maintenance reminder date
- Helpdesk tickets, assignment fields, comments, internal notes, first-response/resolution timestamps, SLA due date field, and CSV report
- Employee directory, linked employee/system accounts, departments and locations

### Audit, documents and reporting

- Physical inventory audits with scoped checklist generation, registered/unregistered findings, duplicate prevention, verifier/time capture, evidence upload, scan/manual capture, and audit close guard
- Private document uploads/downloads for supported record types, permission checks and file validation
- CSV exports for assets, consumables, posted depreciation, maintenance, tickets, assignments, transfers, procurement, audits, disposals, software licenses, employees, departments, locations, categories and suppliers; PDF and XLSX exports for report registers (installed from the committed Composer lockfile; target PHP extensions still need verification)
- Database backup generation/download/deletion using `mysqldump`; configurable daily scheduling at 01:30 Asia/Manila and retention pruning; restoration remains a controlled external operation with a separate-database restore runbook

## Operational setup notes

### Scheduled reminders

The scheduler runs `inventory:backup` daily at 01:30 Asia/Manila when automatic backups are enabled in System Settings, and prunes expired backup records only after a successful dump. It runs `inventory:send-reminders` daily at 08:00 Asia/Manila for warranty expiry, software license expiry, scheduled/next maintenance, and low stock. It runs `inventory:escalate-approvals` hourly to notify approvers and active Super Administrators when configured approval steps exceed their escalation threshold. It also runs `inventory:depreciate` monthly on the first day at 08:30 Asia/Manila, posting the previous month's straight-line entries for assets with an explicitly configured straight-line method and useful life. On Windows, create a Task Scheduler task that runs every minute:

```text
Program: C:\laragon\bin\php\php-8.x.x\php.exe
Arguments: artisan schedule:run
Start in: C:\laragon\www\it-inventory
```

Use the actual PHP executable path shown by your Laragon installation. The application scheduler decides which commands are due.

### Email alerts

The default `.env.example` uses `MAIL_MAILER=log`, so email messages are written to Laravel logs during local development. To send real email, configure the `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, and `MAIL_FROM_NAME` settings for your organization's SMTP service, then run `php artisan optimize:clear`.

### Database backups

The backup feature requires `mysqldump.exe` to be available in the process PATH. If it is not, configure `MYSQLDUMP_PATH` in `.env` with the full path to Laragon's `mysqldump.exe`. Enable automatic daily backups and configure retention under System Settings. Backup files are written to private application storage. The application creates and downloads backups; it does not provide in-app restoration. Follow `docs/BACKUP_RESTORE.md` to perform a restore drill into a separate test database.

### Employee self-service

To use `My Equipment`, create a user account and link it to the corresponding employee record using the **Linked system account** field in the Employees register. The employee role receives the self-service permission. Employees can view linked assigned equipment and submit support tickets.

## Dashboard HTTP 500 troubleshooting

The dashboard now isolates database-query failures per metric and displays a warning instead of failing the entire page. Review the corresponding exception in `storage/logs/laravel.log`, then run:

```bash
php artisan migrate
php artisan optimize:clear
php artisan inventory:doctor
```

The latest office-ownership migration is important for non-super-admin accounts because office-scoped models require `office_id` columns. Never use `migrate:fresh` on a database containing inventory data. If `/dashboard` still returns HTTP 500, capture the new log entry created at the exact time of the request; an older log does not identify a newer failure.

## Validation and troubleshooting

Run the included environment check after installing dependencies and migrating:

```bash
php artisan inventory:doctor
```

It checks the application key, PHP version and required PHP extensions, writable directories, frontend assets, database connectivity, required tables, every column used by the generic inventory forms, creator/audit columns on tracked models, operational workflow columns, and unapplied migrations. A successful connection alone does not mean the schema is ready to save records. For application errors, inspect `storage/logs/laravel.log`.

For an existing database, apply only pending migrations (never recreate the database):

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan inventory:doctor
```

The doctor command verifies the migration/schema prerequisites. It does not replace a live create, update, or transaction test of each workflow.

Preview depreciation without saving any ledger rows:

```bash
php artisan inventory:depreciate --dry-run
```

Post the previous month (the scheduled default) or a specific cutoff period:

```bash
php artisan inventory:depreciate
php artisan inventory:depreciate --as-of=2026-10-31
```

The default depreciation start convention is `next_month`; change it under System Settings only after the accounting/property office confirms the convention. The method and useful life must also be configured on the asset or its category. No depreciation entries are posted for `not_configured` categories or unsupported methods. The calculation currently posts straight-line only. Assets/categories set to `not_configured`, or without a useful life, are skipped. The posting command is idempotent by asset and month.

Run the automated smoke tests after enabling the required PHP extensions:

```bash
composer test
```

The Composer test script invokes PHPUnit directly. This project does not install Laravel's optional `artisan test` command, so `php artisan test` is not the supported test entry point. The smoke tests cover the health endpoint, login screen rendering, and required workflow route registration. A TOTP RFC test-vector test is included for the authenticator algorithm. They do not replace database-backed acceptance tests. Validate the full workflow on the target MySQL server by creating an asset, assigning/returning it, transferring it, moving stock, creating/approving/receiving a procurement, registering the received asset, completing/cancelling maintenance, recording/closing an audit, disposing an asset, and verifying the exported CSVs.

## Scope and external integrations

This release includes normalized PR/quotation/PO/receiving screens, document version metadata, PDF/XLSX export entry points, and core stock-count and preventive-maintenance workflows. It has not yet been accepted against a live MySQL installation. The detailed 24-module coverage and remaining work are tracked in `docs/IMPLEMENTATION_STATUS.md`; the supplied specification is preserved as `docs/IT_Inventory_Build_Plan.md`. The configurable sequential/parallel approval-flow designer currently supports Procurement and Asset Disposal; workflow hooks for assignment, transfer, stock adjustment, maintenance, and individual audit findings remain outstanding. Other gaps include the complete report catalogue and filter dimensions, document taxonomy/retention and version recovery, depreciation methods other than straight-line, public REST API, SMS gateway delivery, queue-backed mail retry, MFA recovery codes, and a tested off-server backup restore drill. A schema table or menu entry alone is not a completed module. See `docs/IMPLEMENTATION_STATUS.md` for the status of all 24 required modules and the MySQL/Laragon acceptance checklist. CSV exports can be opened in Excel; printable HTML views are available for custody acknowledgment. These additions require organization-specific integration choices and deployment-level testing.

External UI resources (Bootstrap, Bootstrap Icons, Google Fonts, QRCode.js, JsBarcode, and Chart.js) are referenced from CDNs in this version. For an air-gapped or restricted network, vendor the required assets locally and remove external runtime dependencies.

## Production security checklist

- Set `APP_ENV=production`, `APP_DEBUG=false`, and serve only over HTTPS.
- Use a unique application key and a least-privileged MySQL account.
- Set the web server document root to the `public/` directory only.
- Keep `.env`, private documents, signatures, and database backups outside the public web root.
- Restrict `storage/` and `bootstrap/cache/` write access to the application service account.
- Configure SMTP and verify actual delivery before relying on email notifications.
- Configure Windows Task Scheduler and test reminder execution.
- Test backup restoration separately; a successful dump is not proof that a restore will succeed.
- Review every role and permission with non-admin test accounts.
- Run the application's smoke tests and complete the database-backed acceptance scenarios before production use.
