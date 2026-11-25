# IT Inventory Management System: Build Plan

Stack: Laravel (11/12), MySQL 8, Blade, Bootstrap 5, Alpine.js, Chart.js, DataTables, Dompdf, Laravel Excel, Spatie packages.

---

## 1. Project Setup (Week 0)

- [ ] `laravel new it-inventory`, configure MySQL, `.env`, timezone `Asia/Manila`
- [ ] Install packages:
  - `spatie/laravel-permission` (roles and permissions)
  - `spatie/laravel-activitylog` or custom audit logger (see Phase 9)
  - `maatwebsite/excel`, `barryvdh/laravel-dompdf`
  - `simplesoftwareio/simple-qrcode`, `picqer/php-barcode-generator`
  - `yajra/laravel-datatables-oracle`
  - `laravel/breeze` (Blade stack) for auth scaffolding, then restyle with Bootstrap 5
- [ ] Base layout: sidebar partial driven by a config array and permission checks (`@can`)
- [ ] Conventions:
  - One Form Request per create/update action
  - Policies per model; thin controllers; business logic in `app/Services/*`
  - Enums (PHP 8.1) for statuses and conditions
  - Soft deletes on all master and transactional tables
  - `created_by`, `updated_by` columns via a shared trait
  - DB transactions for assignment, transfer, stock movement, disposal
- [ ] CI: PHPUnit/Pest + Pint on each push

---

## 2. Database Schema

Naming: plural snake_case tables, `bigIncrements` PKs, FKs indexed, `softDeletes()` unless noted.

### 2.1 Identity and Access
| Table | Key columns |
|---|---|
| `users` | id, employee_id (FK nullable), name, email, password, is_active, last_login_at, mfa_secret |
| `roles`, `permissions`, `model_has_roles`, `role_has_permissions` | via Spatie |
| `login_histories` | user_id, ip, user_agent, status (success/failed), logged_at (no soft delete) |

### 2.2 Organization
| Table | Key columns |
|---|---|
| `departments` | id, parent_id (self FK), code, name, head_employee_id |
| `employees` | id, employee_no, full_name, position_rank, designation, department_id, email, phone, status (active/transferred/separated) |
| `locations` | id, parent_id (self FK), type (building/floor/room/storage/data_center), code, name, qr_code |
| `suppliers` | id, name, contact_person, address, phone, email, tin, accreditation_status, notes |

> `locations` as a self-referencing tree covers Building > Floor > Room > Storage in one table.

### 2.3 Assets
| Table | Key columns |
|---|---|
| `categories` | id, parent_id, code, name, type (asset/consumable/software), depreciation_method, useful_life_months, salvage_percent, custom_fields (json) |
| `assets` | id, asset_tag (unique), serial_no, name, category_id, brand, model, specs (json), purchase_date, purchase_cost, supplier_id, purchase_order_id, warranty_expiry, department_id, location_id, current_employee_id, condition (enum), status (enum), image_path, remarks |
| `asset_assignments` | id, asset_id, assignee_type + assignee_id (employee/department/location, polymorphic), type (temporary/permanent), issued_at, due_back_at, returned_at, status, approved_by, ack_signature_path, ack_at |
| `asset_transfers` | id, asset_id, from_type/from_id, to_type/to_id, transfer_type, reason, status, requested_by, approved_by, received_by, received_at |
| `asset_histories` | id, asset_id, event, old_values (json), new_values (json), actor_id, created_at (append-only) |
| `depreciation_entries` | id, asset_id, period, amount, book_value |

### 2.4 Inventory (Consumables)
| Table | Key columns |
|---|---|
| `stock_items` | id, sku, name, category_id, unit, min_level, reorder_level, qty_on_hand, unit_cost, location_id |
| `stock_movements` | id, stock_item_id, type (receive/issue/return/adjust/transfer/damaged/lost), qty (signed), unit_cost, batch_no, expiry_date, reference (polymorphic), performed_by, created_at (append-only) |
| `stock_counts`, `stock_count_items` | physical count header and lines with system_qty, counted_qty, variance |

> `qty_on_hand` is a cached value. The ledger (`stock_movements`) is the source of truth; update both inside one transaction.

### 2.5 Software and Licenses
| Table | Key columns |
|---|---|
| `software` | id, name, version, publisher, category_id |
| `licenses` | id, software_id, license_key (encrypted cast), type, seats_total, purchase_date, expiry_date, cost, subscription_cycle, supplier_id |
| `license_assignments` | id, license_id, assignable_type/id (employee or asset), assigned_at, revoked_at |
| `license_renewals` | id, license_id, renewed_on, new_expiry, cost |

### 2.6 Procurement
| Table | Key columns |
|---|---|
| `purchase_requests` | id, pr_no, requested_by, department_id, purpose, status, total_estimated |
| `purchase_request_items` | id, purchase_request_id, description, category_id, qty, est_unit_cost |
| `quotations` | id, purchase_request_id, supplier_id, total, is_selected, file_path |
| `purchase_orders` | id, po_no, purchase_request_id, supplier_id, ordered_at, total, status |
| `receiving_reports` | id, rr_no, purchase_order_id, received_at, inspected_by, accepted_at, status |
| `receiving_report_items` | id, receiving_report_id, po_item/description, qty_received, registered_as (asset/stock) |

Statuses: Draft, Submitted, Pending Approval, Approved, For Purchase, Ordered, Partially Received, Received, Inspected, Completed, Cancelled.

### 2.7 Maintenance and Helpdesk
| Table | Key columns |
|---|---|
| `maintenance_schedules` | id, asset_id or category_id, interval_days, next_due, last_done |
| `work_orders` | id, wo_no, asset_id, type (preventive/corrective), priority, technician_id, status, started_at, completed_at, cost, parts_used (json), service_provider, downtime_minutes, findings |
| `tickets` | id, ticket_no, requester_id, asset_id (nullable), category, priority, subject, description, assignee_id, status, sla_due_at, first_response_at, resolved_at, rating |
| `ticket_comments` | id, ticket_id, user_id, body, is_internal |

### 2.8 Audit, Disposal, Cross-Cutting
| Table | Key columns |
|---|---|
| `inventory_audits` | id, title, scope (location/department/all), scheduled_for, status, lead_id |
| `inventory_audit_items` | id, audit_id, asset_id (nullable for unregistered), result (Verified/Not Found/Transferred/Damaged/Unregistered/Incorrect Location/Incorrect Custodian), seen_location_id, seen_employee_id, condition, photo_path, scanned_at, auditor_id |
| `disposals` | id, asset_id, justification, evaluation_report, method, disposal_date, disposal_value, salvage_value, status, witnesses (json) |
| `documents` | id, documentable_type/id, type, title, path, version, mime, size, uploaded_by |
| `approval_flows`, `approval_flow_steps` | flow per subject type, ordered steps, approver role or user, mode (sequential/parallel) |
| `approvals` | id, approvable_type/id, step_id, approver_id, decision, remarks, decided_at |
| `audit_logs` | id, user_id, action, auditable_type/id, old (json), new (json), ip, user_agent, created_at (no update/delete; revoke UPDATE/DELETE grants for the app DB user) |
| `settings` | key, value (json), group |
| `notifications` | Laravel default |

### 2.9 Key Relationships
- Department hasMany Employees, Assets; Employee belongsTo Department
- Asset belongsTo Category, Supplier, Location, Department, Employee (custodian)
- Asset hasMany Assignments, Transfers, WorkOrders, Tickets, Documents (morph), Histories, AuditItems
- StockItem hasMany StockMovements
- License belongsTo Software; hasMany LicenseAssignments
- PurchaseRequest hasMany Items, Quotations; hasOne PurchaseOrder; PurchaseOrder hasMany ReceivingReports
- Any approvable model morphMany Approvals

---

## 3. Phased Task Breakdown

Estimates assume 1-2 developers. Adjust to your team.

### Phase 1: Foundation, Auth, RBAC (1-2 wks)
- [ ] Auth, password policy, lockout, login history
- [ ] Users CRUD, activate/deactivate, password reset
- [ ] Roles seeded: Super Admin, System Admin, IT Inventory Manager, Property Custodian, Technician, Procurement Officer, Department Head, Department Staff, Auditor, Employee
- [ ] Permission matrix UI (module x CRUD)
- [ ] Layout, sidebar, dashboard shell
- [ ] **Done when:** each role sees only its menu items; unauthorized routes return 403 (feature tests)

### Phase 2: Masters and Asset Core (2-3 wks)
- [ ] Departments, Employees, Locations (tree), Suppliers, Categories
- [ ] Asset CRUD, auto asset tag (`PREFIX-YYYY-00001` via settings + DB-safe sequence), image and document upload
- [ ] DataTables list with search, filters, sort; asset detail with timeline
- [ ] Bulk import (Excel/CSV with validation report), bulk export
- [ ] **Done when:** 1,000-row import completes with row-level error report; asset list stays fast with indexes

### Phase 3: Accountability and QR (2 wks)
- [ ] Assignment, return, reassignment (single and multiple assets)
- [ ] Transfers (employee, department, location; temporary/permanent) with receiving confirmation
- [ ] PDFs: Property Acknowledgment Receipt, Issuance Form, Turnover Form, Accountability Report
- [ ] QR and barcode generation, single and bulk label printing, QR landing page (`/a/{tag}`)
- [ ] Overdue-return monitoring
- [ ] **Done when:** an asset can't be double-assigned; every change writes an `asset_histories` row

### Phase 4: Stock, Procurement, Suppliers (2-3 wks)
- [ ] Stock items, receive/issue/return/adjust/transfer with ledger
- [ ] Min-level and out-of-stock alerts; physical stock count and variance
- [ ] PR > quotation > PO > receiving report > inspection > auto-register assets/stock
- [ ] Procurement status tracking, annual acquisition report
- [ ] **Done when:** concurrent issues never drive stock negative (row locking test)

### Phase 5: Maintenance and Helpdesk (2 wks)
- [ ] Maintenance schedules and generated work orders (scheduler)
- [ ] Work order lifecycle, technician assignment, cost, parts, downtime, attachments
- [ ] Tickets: submit, assign, comment (public/internal), escalate, reopen, close, rate
- [ ] SLA timers and breach flags
- [ ] Asset status auto-switches to Maintenance and back

### Phase 6: Software and Licenses (1-2 wks)
- [ ] Software registry, licenses (encrypted keys), seat tracking
- [ ] Assign to employee/asset; compliance (assigned > purchased) flag
- [ ] Expiry monitoring, renewal history, audit report

### Phase 7: Physical Inventory and Auditing (2 wks)
- [ ] Audit creation scoped by location/department; checklist generation
- [ ] Mobile-friendly scan page (camera QR via JS library), result capture, photo upload
- [ ] Unregistered and missing asset recording, reconciliation, findings and corrective actions
- [ ] Audit report PDF/Excel

### Phase 8: Reports, Analytics, Documents (2 wks)
- [ ] Dashboard widgets (25 KPIs in spec) with cached queries
- [ ] 20 reports with filters and PDF/XLSX/CSV export
- [ ] Document repository: categories, versions, preview, permissioned download
- [ ] Depreciation job and valuation reports

### Phase 9: Approvals, Notifications, Audit Trail (2 wks)
- [ ] Configurable approval engine (sequential/parallel, return for revision, escalation); wire into assignment, transfer, PR, stock adjustment, disposal, maintenance, audit findings
- [ ] Notification triggers (in-app and email via queues; SMS optional)
- [ ] Audit log writer (observer/trait), searchable viewer, export
- [ ] Disposal module (evaluation > approval > disposed)

### Phase 10: Hardening and Extras (1-2 wks)
- [ ] Scheduled backups (`spatie/laravel-backup`), off-server storage, restore drill
- [ ] REST API (Sanctum) for assets, assignments, scans
- [ ] MFA, session management, security review, load test, deployment runbook

---

## 4. Module Checklist (status tracker)

| # | Module | Phase | Status |
|---|---|---|---|
| 1 | Dashboard | 1, 8 | ☐ |
| 2 | IT Assets | 2 | ☐ |
| 3 | Categories | 2 | ☐ |
| 4 | Assignment | 3 | ☐ |
| 5 | Stock and Consumables | 4 | ☐ |
| 6 | Software and Licenses | 6 | ☐ |
| 7 | Procurement | 4 | ☐ |
| 8 | Maintenance | 5 | ☐ |
| 9 | Helpdesk | 5 | ☐ |
| 10 | Employees and Departments | 2 | ☐ |
| 11 | Locations | 2 | ☐ |
| 12 | Physical Inventory | 7 | ☐ |
| 13 | Transfers and Turnover | 3 | ☐ |
| 14 | Disposal | 9 | ☐ |
| 15 | Suppliers | 2, 4 | ☐ |
| 16 | Documents | 8 | ☐ |
| 17 | Reports | 8 | ☐ |
| 18 | QR/Barcode | 3 | ☐ |
| 19 | Users and Access | 1 | ☐ |
| 20 | Approvals | 9 | ☐ |
| 21 | Notifications | 9 | ☐ |
| 22 | Settings | 1-2 | ☐ |
| 23 | Audit Trail | 9 | ☐ |
| 24 | Backup | 10 | ☐ |

---

## 5. Testing Priorities

1. Assignment/transfer: no double assignment, custodian and location stay consistent
2. Stock ledger: totals match movements; race-condition test
3. Approval engine: sequential order, rejection, return for revision, escalation
4. RBAC: each role x module x action matrix
5. Audit log immutability and completeness
6. Import validation and rollback on failure
7. Backup restore into a clean database

## 6. Key Risks and Decisions to Make Early

- **Approvals scope:** build the generic engine in Phase 9 but design Phases 3-5 with a `status` + `approvable` hook so retrofitting is cheap.
- **Asset tag format:** decide the prefix scheme before bulk-importing legacy data.
- **Legacy data:** plan a cleanup/import pass for existing inventory records before Phase 3.
- **Depreciation method:** confirm straight-line vs. other with your accounting or property office.
- **Audit log DB permissions:** enforce append-only at the database level, not just in code.
- **Mobile scanning:** browser camera scanning is enough for MVP; native app can wait.

## 7. Suggested MVP Cut

Phases 1-3, a lean Phase 5 (maintenance only), basic Phase 7, and audit logging. That gives you the core accountability loop: register > assign > maintain > verify.
