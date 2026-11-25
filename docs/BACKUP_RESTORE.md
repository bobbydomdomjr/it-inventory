# IT Inventory Management System — Backup and Restore Runbook

## Scope and safety

The application creates MySQL logical backups through `mysqldump` and stores the SQL file in Laravel's private local storage (`storage/app/private/backups`). The application supports backup creation, listing, download, and retention pruning. It does **not** run an in-app database restore.

A backup is not considered proven until it has been restored and checked on a separate test database. Do not test restoration against the live `it_inventory` database.

## Configure backup creation

1. Confirm the PHP/web-server account can write to `storage/` and `bootstrap/cache/`.
2. Confirm `mysqldump.exe` is accessible to the PHP process. If it is not on `PATH`, set `MYSQLDUMP_PATH` in `.env` to its full path, for example:

   ```dotenv
   MYSQLDUMP_PATH="C:/laragon/bin/mysql/mysql-8.x.x-winx64/bin/mysqldump.exe"
   ```

   Use the actual path from your Laragon installation.
3. Sign in with an account that has the `backups.manage` and `settings.manage` permissions.
4. Open **System Settings → Backup policy**, enable automatic daily backups, and set the retention period.
5. Configure Windows Task Scheduler to execute `php artisan schedule:run` every minute (see the main README).
6. Create one manual backup and download it. Copy it to an access-controlled off-server destination.

The scheduled backup runs at 1:30 AM Asia/Manila. Retention pruning occurs only after a successful new dump. A failed backup does not trigger pruning.

## Restore drill on a separate database (MySQL / Laragon)

1. Obtain an approved backup file and copy it to a restricted temporary folder outside the web root.
2. Check its file size and calculate a checksum before moving it:

   ```powershell
   Get-FileHash .\it-inventory-backup.sql -Algorithm SHA256
   ```

3. Create a disposable database, for example `it_inventory_restore_test`, using phpMyAdmin or MySQL CLI.
4. Restore the backup into that disposable database. Open Laragon Terminal and use the actual MySQL client path if it is not on `PATH`:

   ```powershell
   mysql -h 127.0.0.1 -P 3306 -u root -p it_inventory_restore_test < .\it-inventory-backup.sql
   ```

   If the local MySQL account has no password, omit `-p`. Never put a production password directly into a command line or script.
5. Verify the restore by connecting to the test database, checking the expected tables, reviewing record counts, and running read-only application checks against a separate `.env`/test deployment configured for `it_inventory_restore_test`.
6. Record the restore date, backup filename, checksum, database version, duration, validation results, and operator.
7. Drop the disposable test database only after the restore drill has been documented and approved.

## Operational controls

- Keep backups private; never store them under `public/` or expose the storage directory through the web server.
- Keep at least one independent off-server copy under the organization's retention policy.
- Restrict backup download/delete permission to designated administrators.
- Protect `.env` and database credentials; use a least-privileged database account.
- Review retention requirements with the organization's records-management policy before enabling automatic deletion.
- Repeat the restore drill periodically and after major schema changes.
- A successful `mysqldump` exit code proves only that the dump command completed; it does not prove that the file can be restored.
