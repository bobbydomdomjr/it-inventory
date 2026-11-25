-- IT Inventory database office-ownership repair
-- Generated from the supplied it_inventory.sql dump.
-- Run this against the existing it_inventory database AFTER taking a backup.
-- It creates/activates MAIN and fills only NULL office_id values.
-- It does not delete, truncate, or overwrite any existing office assignment.

START TRANSACTION;

INSERT INTO `offices`
    (`code`, `name`, `short_name`, `is_active`, `created_at`, `updated_at`, `deleted_at`)
VALUES
    ('MAIN', 'Main Office', 'MAIN', 1, NOW(), NOW(), NULL)
ON DUPLICATE KEY UPDATE
    `id` = LAST_INSERT_ID(`id`),
    `is_active` = 1,
    `deleted_at` = NULL,
    `updated_at` = NOW();

SET @main_office_id := (SELECT `id` FROM `offices` WHERE `code` = 'MAIN' LIMIT 1);

SET @main_office_id := COALESCE(@main_office_id, LAST_INSERT_ID());

UPDATE `offices` SET `is_active` = 1, `deleted_at` = NULL WHERE `id` = @main_office_id;

UPDATE `users` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `departments` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `locations` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `asset_categories` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `suppliers` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `employees` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `assets` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `inventory_items` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `software_licenses` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `assignments` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `asset_transfers` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `stock_transactions` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `maintenance_records` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `support_tickets` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `procurements` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `work_orders` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `documents` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `receiving_reports` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `supplier_evaluations` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `asset_disposals` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `purchase_requests` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `purchase_orders` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `quotations` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `purchase_request_items` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `receiving_report_items` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `licenses` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `stock_counts` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `stock_count_items` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `maintenance_schedules` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `inventory_audit_items` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `license_renewals` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `license_assignments` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `software_license_assignments` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;
UPDATE `ticket_comments` SET `office_id` = @main_office_id WHERE `office_id` IS NULL;

COMMIT;

-- Verification: both queries should show the Main Office and zero unassigned records.
SELECT `id`, `code`, `name`, `is_active` FROM `offices` WHERE `id` = @main_office_id;
SELECT 'users' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `users` WHERE `office_id` IS NULL;
SELECT 'departments' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `departments` WHERE `office_id` IS NULL;
SELECT 'locations' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `locations` WHERE `office_id` IS NULL;
SELECT 'asset_categories' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `asset_categories` WHERE `office_id` IS NULL;
SELECT 'suppliers' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `suppliers` WHERE `office_id` IS NULL;
SELECT 'employees' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `employees` WHERE `office_id` IS NULL;
SELECT 'assets' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `assets` WHERE `office_id` IS NULL;
SELECT 'inventory_items' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `inventory_items` WHERE `office_id` IS NULL;
SELECT 'software_licenses' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `software_licenses` WHERE `office_id` IS NULL;
SELECT 'assignments' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `assignments` WHERE `office_id` IS NULL;
SELECT 'asset_transfers' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `asset_transfers` WHERE `office_id` IS NULL;
SELECT 'stock_transactions' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `stock_transactions` WHERE `office_id` IS NULL;
SELECT 'maintenance_records' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `maintenance_records` WHERE `office_id` IS NULL;
SELECT 'support_tickets' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `support_tickets` WHERE `office_id` IS NULL;
SELECT 'procurements' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `procurements` WHERE `office_id` IS NULL;
SELECT 'work_orders' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `work_orders` WHERE `office_id` IS NULL;
SELECT 'documents' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `documents` WHERE `office_id` IS NULL;
SELECT 'receiving_reports' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `receiving_reports` WHERE `office_id` IS NULL;
SELECT 'supplier_evaluations' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `supplier_evaluations` WHERE `office_id` IS NULL;
SELECT 'asset_disposals' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `asset_disposals` WHERE `office_id` IS NULL;
SELECT 'purchase_requests' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `purchase_requests` WHERE `office_id` IS NULL;
SELECT 'purchase_orders' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `purchase_orders` WHERE `office_id` IS NULL;
SELECT 'quotations' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `quotations` WHERE `office_id` IS NULL;
SELECT 'purchase_request_items' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `purchase_request_items` WHERE `office_id` IS NULL;
SELECT 'receiving_report_items' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `receiving_report_items` WHERE `office_id` IS NULL;
SELECT 'licenses' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `licenses` WHERE `office_id` IS NULL;
SELECT 'stock_counts' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `stock_counts` WHERE `office_id` IS NULL;
SELECT 'stock_count_items' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `stock_count_items` WHERE `office_id` IS NULL;
SELECT 'maintenance_schedules' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `maintenance_schedules` WHERE `office_id` IS NULL;
SELECT 'inventory_audit_items' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `inventory_audit_items` WHERE `office_id` IS NULL;
SELECT 'license_renewals' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `license_renewals` WHERE `office_id` IS NULL;
SELECT 'license_assignments' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `license_assignments` WHERE `office_id` IS NULL;
SELECT 'software_license_assignments' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `software_license_assignments` WHERE `office_id` IS NULL;
SELECT 'ticket_comments' AS `table_name`, COUNT(*) AS `unassigned_rows` FROM `ticket_comments` WHERE `office_id` IS NULL;