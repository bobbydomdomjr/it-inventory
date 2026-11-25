@extends('layouts.app')
@section('title', 'System Settings')
@section('breadcrumb', 'System Settings')

@section('content')
@php
    $value = fn (string $key, $fallback = '') => old($key, $settings[$key] ?? $fallback);
    $enabled = fn (string $key, bool $fallback = false) => (bool) old($key, $settings[$key] ?? $fallback);
    $navLinks = [
        ['organization', 'bi-building', 'Organization'],
        ['configuration', 'bi-sliders', 'Reference data'],
        ['assets', 'bi-upc-scan', 'Asset rules'],
        ['notifications', 'bi-bell', 'Notifications'],
        ['email', 'bi-envelope', 'Email / SMTP'],
        ['regional', 'bi-globe2', 'Regional'],
        ['backup', 'bi-database-check', 'Backup'],
        ['data', 'bi-arrow-left-right', 'Import / Export'],
        ['preferences', 'bi-toggles', 'Preferences'],
    ];
@endphp

<div class="page-heading">
    <div>
        <div class="eyebrow">ADMINISTRATION</div>
        <h1>System Settings and Configuration</h1>
        <p>Manage the organization profile, inventory rules, integrations, and system-wide preferences.</p>
    </div>
    <div class="heading-actions">
        <a href="{{ route('mfa.settings') }}" class="btn btn-outline-secondary"><i class="bi bi-shield-lock me-1"></i>Account MFA</a>
        <button form="systemSettingsForm" type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Save settings</button>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger"><strong>Please review the highlighted settings.</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<nav class="settings-index" aria-label="Settings sections">
    @foreach ($navLinks as [$id, $icon, $label])
        <a href="#settings-{{ $id }}"><i class="bi {{ $icon }}"></i><span>{{ $label }}</span></a>
    @endforeach
</nav>

<form id="systemSettingsForm" method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    <section class="panel settings-section" id="settings-organization">
        <div class="panel-header"><div><h2>Organization profile and system branding</h2><p>Identity displayed in the application shell, sign-in page, and supported reports.</p></div><span class="settings-section-number">01</span></div>
        <div class="settings-grid">
            <div class="form-field"><label class="form-label" for="organization_name">Organization name</label><input id="organization_name" class="form-control" name="organization_name" required maxlength="150" value="{{ $value('organization_name', config('app.name')) }}"></div>
            <div class="form-field"><label class="form-label" for="system_name">System name</label><input id="system_name" class="form-control" name="system_name" required maxlength="120" value="{{ $value('system_name', config('app.name', 'IT Inventory Management System')) }}"></div>
            <div class="form-field"><label class="form-label" for="system_short_name">System short name</label><input id="system_short_name" class="form-control" name="system_short_name" required maxlength="40" value="{{ $value('system_short_name', 'IT Inventory') }}"><small class="form-text">Displayed in the sidebar brand area.</small></div>
            <div class="form-field"><label class="form-label" for="organization_email">Organization contact email</label><input id="organization_email" type="email" class="form-control" name="organization_email" maxlength="150" value="{{ $value('organization_email') }}"></div>
            <div class="form-field"><label class="form-label" for="organization_phone">Organization contact number</label><input id="organization_phone" class="form-control" name="organization_phone" maxlength="50" value="{{ $value('organization_phone') }}"></div>
            <div class="form-field field-full"><label class="form-label" for="organization_address">Organization address</label><textarea id="organization_address" class="form-control" name="organization_address" rows="2" maxlength="500">{{ $value('organization_address') }}</textarea></div>
            <div class="form-field"><label class="form-label" for="system_logo">System logo</label><input id="system_logo" type="file" class="form-control" name="system_logo" accept="image/png,image/jpeg,image/webp"><small class="form-text">PNG, JPG or WebP; maximum 2 MB. Use a transparent logo for best results.</small></div>
            <div class="form-field"><label class="form-label d-block">Current logo</label><div class="settings-logo-preview" id="systemLogoPreview">@if($settings['system_logo_url'] ?? false)<img id="systemLogoPreviewImage" src="{{ $settings['system_logo_url'] }}" alt="Current system logo">@elseif($settings['system_logo_missing'] ?? false)<span class="text-warning small" id="systemLogoPreviewPlaceholder">The saved logo file is missing. Upload the logo again.</span>@else<img id="systemLogoPreviewImage" alt="Selected system logo preview" hidden><span class="text-secondary small" id="systemLogoPreviewPlaceholder">No custom logo uploaded</span>@endif</div><label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_system_logo" value="1" @checked($enabled('remove_system_logo', false))><span class="form-check-label">Remove current logo</span></label></div>
            <div class="form-field"><label class="form-label" for="brand_primary">Primary brand color</label><div class="d-flex align-items-center gap-2"><input id="brand_primary" type="color" class="form-control form-control-color" name="brand_primary" value="{{ $value('brand_primary', '#315f9f') }}"><input id="brand_primary_hex" class="form-control" value="{{ strtoupper($value('brand_primary', '#315f9f')) }}" readonly aria-label="Primary color hex value"></div></div>
            <div class="form-field"><label class="form-label" for="brand_accent">Accent brand color</label><div class="d-flex align-items-center gap-2"><input id="brand_accent" type="color" class="form-control form-control-color" name="brand_accent" value="{{ $value('brand_accent', '#0caa6a') }}"><input id="brand_accent_hex" class="form-control" value="{{ strtoupper($value('brand_accent', '#0caa6a')) }}" readonly aria-label="Accent color hex value"></div></div>
        </div>
    </section>

    <section class="panel settings-section" id="settings-configuration">
        <div class="panel-header"><div><h2>Department, location and reference data configuration</h2><p>Use the dedicated registers to maintain organizational reference records.</p></div><span class="settings-section-number">02</span></div>
        <div class="settings-link-grid">
            @if($canManageDepartments)<a class="settings-link-card" href="{{ route('inventory.index', 'departments') }}"><span class="settings-link-icon"><i class="bi bi-diagram-3"></i></span><span><strong>Department configuration</strong><small>Department codes, hierarchy and department heads</small></span><i class="bi bi-arrow-up-right"></i></a>@endif
            @if($canManageLocations)<a class="settings-link-card" href="{{ route('inventory.index', 'locations') }}"><span class="settings-link-icon"><i class="bi bi-geo-alt"></i></span><span><strong>Location configuration</strong><small>Buildings, floors, rooms, storage and data centers</small></span><i class="bi bi-arrow-up-right"></i></a>@endif
            @if($canManageCategories)<a class="settings-link-card" href="{{ route('inventory.index', 'categories') }}"><span class="settings-link-icon"><i class="bi bi-tags"></i></span><span><strong>Asset category configuration</strong><small>Category type, useful life and depreciation rules</small></span><i class="bi bi-arrow-up-right"></i></a>@endif
        </div>
        <div class="settings-grid settings-reference-grid">
            <div class="form-field field-full"><label class="form-label">Asset status labels</label><p class="form-text mt-0">Labels can be customized, but the underlying status codes remain fixed to protect workflow rules and historical data.</p>
                <div class="settings-inline-fields">
                    @foreach($statusLabels as $key => $defaultLabel)
                        <div><label class="form-label" for="asset_status_label_{{ $key }}">{{ ucfirst(str_replace('_', ' ', $key)) }}</label><input id="asset_status_label_{{ $key }}" class="form-control" name="asset_status_label_{{ $key }}" required maxlength="60" value="{{ $value('asset_status_label_'.$key, $defaultLabel) }}"></div>
                    @endforeach
                </div>
            </div>
            <div class="form-field field-full"><label class="form-label">Asset condition labels</label>
                <div class="settings-inline-fields settings-condition-fields">
                    @foreach($conditionLabels as $key => $defaultLabel)
                        <div><label class="form-label" for="asset_condition_label_{{ strtolower($key) }}">{{ $key }}</label><input id="asset_condition_label_{{ strtolower($key) }}" class="form-control" name="asset_condition_label_{{ strtolower($key) }}" required maxlength="60" value="{{ $value('asset_condition_label_'.strtolower($key), $defaultLabel) }}"></div>
                    @endforeach
                </div>
            </div>
            <div class="form-field field-full"><label class="form-label" for="inventory_units">Inventory unit configuration</label><textarea id="inventory_units" name="inventory_units" class="form-control" rows="4" required>{{ old('inventory_units', $settings['inventory_units_text']) }}</textarea><small class="form-text">One unit per line; maximum 40 values. These options are used when creating or editing consumable stock records.</small></div>
        </div>
    </section>

    <section class="panel settings-section" id="settings-assets">
        <div class="panel-header"><div><h2>Asset identification and depreciation settings</h2><p>Control generated asset tags and default valuation policy for new records.</p></div><span class="settings-section-number">03</span></div>
        <div class="settings-grid">
            <div class="form-field"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="automatic_asset_numbering" value="1" @checked($enabled('automatic_asset_numbering', true))><span class="form-check-label fw-semibold">Enable automatic asset numbering</span></label><small class="form-text">When disabled, users must enter an asset ID manually.</small></div>
            <div class="form-field"><label class="form-label" for="asset_tag_prefix">Asset ID prefix</label><input id="asset_tag_prefix" class="form-control" name="asset_tag_prefix" required maxlength="12" pattern="[A-Za-z0-9][A-Za-z0-9_-]*" value="{{ $value('asset_tag_prefix', 'IT') }}"><small class="form-text">New automatically generated IDs use PREFIX-YEAR-SEQUENCE. Existing tags are not changed.</small></div>
            <div class="form-field"><label class="form-label" for="default_useful_life_months">Default useful life (months)</label><input id="default_useful_life_months" type="number" min="1" max="600" class="form-control" name="default_useful_life_months" required value="{{ $value('default_useful_life_months', 48) }}"></div>
            <div class="form-field"><label class="form-label" for="default_depreciation_method">Default depreciation method</label><select id="default_depreciation_method" class="form-select" name="default_depreciation_method"><option value="straight_line" @selected($value('default_depreciation_method', 'straight_line') === 'straight_line')>Straight line</option><option value="not_configured" @selected($value('default_depreciation_method', 'not_configured') === 'not_configured')>Not configured</option></select></div>
            <div class="form-field"><label class="form-label" for="depreciation_start_policy">Depreciation start convention</label><select id="depreciation_start_policy" class="form-select" name="depreciation_start_policy"><option value="next_month" @selected($value('depreciation_start_policy', 'next_month') === 'next_month')>Month after acquisition</option><option value="purchase_month" @selected($value('depreciation_start_policy', 'next_month') === 'purchase_month')>Acquisition month</option></select></div>
            <div class="form-field"><label class="form-label" for="default_salvage_percent">Default salvage value (%)</label><input id="default_salvage_percent" type="number" min="0" max="100" step="0.001" class="form-control" name="default_salvage_percent" value="{{ $value('default_salvage_percent', 0) }}" required><small class="form-text">Applied as a default for new asset valuation records where applicable.</small></div>
        </div>
    </section>

    <section class="panel settings-section" id="settings-notifications">
        <div class="panel-header"><div><h2>Notification and maintenance scheduling settings</h2><p>Configure alert channels, reminder windows, and preventive schedule generation.</p></div><span class="settings-section-number">04</span></div>
        <div class="settings-grid">
            <div class="form-field"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="notifications_enabled" value="1" @checked($enabled('notifications_enabled', true))><span class="form-check-label fw-semibold">Enable scheduled inventory reminder notifications</span></label></div>
            <div class="form-field"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="notify_low_stock" value="1" @checked($enabled('notify_low_stock', true))><span class="form-check-label fw-semibold">Low-stock alerts</span></label><label class="form-label mt-2" for="low_stock_default">Default reorder threshold</label><input id="low_stock_default" type="number" min="0" max="1000000" class="form-control" name="low_stock_default" required value="{{ $value('low_stock_default', 5) }}"></div>
            <div class="form-field"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="notify_warranty" value="1" @checked($enabled('notify_warranty', true))><span class="form-check-label fw-semibold">Warranty expiration alerts</span></label><label class="form-label mt-2" for="warranty_reminder_days">Warranty reminder window (days)</label><input id="warranty_reminder_days" type="number" min="1" max="365" class="form-control" name="warranty_reminder_days" required value="{{ $value('warranty_reminder_days', 30) }}"></div>
            <div class="form-field"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="notify_maintenance" value="1" @checked($enabled('notify_maintenance', true))><span class="form-check-label fw-semibold">Maintenance due alerts</span></label><label class="form-label mt-2" for="maintenance_reminder_days">Maintenance reminder window (days)</label><input id="maintenance_reminder_days" type="number" min="1" max="365" class="form-control" name="maintenance_reminder_days" required value="{{ $value('maintenance_reminder_days', 14) }}"></div>
            <div class="form-field"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="notify_license_expiry" value="1" @checked($enabled('notify_license_expiry', true))><span class="form-check-label fw-semibold">Software license expiration alerts</span></label><label class="form-label mt-2" for="license_reminder_days">License reminder window (days)</label><input id="license_reminder_days" type="number" min="1" max="365" class="form-control" name="license_reminder_days" required value="{{ $value('license_reminder_days', 60) }}"></div>
            <div class="form-field"><label class="form-label" for="maintenance_generation_time">Preventive maintenance generation time</label><input id="maintenance_generation_time" type="time" class="form-control" name="maintenance_generation_time" required value="{{ $value('maintenance_generation_time', '06:00') }}"><small class="form-text">The Laravel Scheduler must be running on the server for this schedule to execute.</small></div>
            <div class="form-field"><label class="form-label" for="notification_delivery_time">Daily inventory alert delivery time</label><input id="notification_delivery_time" type="time" class="form-control" name="notification_delivery_time" required value="{{ $value('notification_delivery_time', '08:00') }}"><small class="form-text">Uses the configured system timezone and the Laravel Scheduler.</small></div>
        </div>
    </section>

    <section class="panel settings-section" id="settings-email">
        <div class="panel-header"><div><h2>Email SMTP configuration</h2><p>SMTP credentials are encrypted before they are stored. The password is never displayed after saving.</p></div><span class="settings-section-number">05</span></div>
        <div class="settings-grid">
            <div class="form-field"><label class="form-label" for="smtp_mailer">Mail transport</label><select id="smtp_mailer" class="form-select" name="smtp_mailer"><option value="smtp" @selected($value('smtp_mailer', config('mail.default', 'log')) === 'smtp')>SMTP server</option><option value="log" @selected($value('smtp_mailer', config('mail.default', 'log')) === 'log')>Log only (no outbound email)</option></select></div>
            <div class="form-field"><label class="form-label" for="smtp_host">SMTP host</label><input id="smtp_host" class="form-control" name="smtp_host" maxlength="190" value="{{ $value('smtp_host', config('mail.mailers.smtp.host', '')) }}" placeholder="smtp.example.com"></div>
            <div class="form-field"><label class="form-label" for="smtp_port">SMTP port</label><input id="smtp_port" type="number" min="1" max="65535" class="form-control" name="smtp_port" value="{{ $value('smtp_port', config('mail.mailers.smtp.port', 587)) }}"></div>
            <div class="form-field"><label class="form-label" for="smtp_encryption">SMTP encryption</label><select id="smtp_encryption" name="smtp_encryption" class="form-select"><option value="tls" @selected($value('smtp_encryption', 'tls') === 'tls')>STARTTLS / TLS</option><option value="ssl" @selected($value('smtp_encryption', 'tls') === 'ssl')>Implicit TLS / SSL</option><option value="none" @selected($value('smtp_encryption', 'tls') === 'none')>None</option></select></div>
            <div class="form-field"><label class="form-label" for="smtp_username">SMTP username</label><input id="smtp_username" class="form-control" name="smtp_username" maxlength="190" autocomplete="off" value="{{ $value('smtp_username') }}"></div>
            <div class="form-field"><label class="form-label" for="smtp_password">SMTP password</label><input id="smtp_password" type="password" class="form-control" name="smtp_password" maxlength="500" autocomplete="new-password" placeholder="{{ $settings['smtp_password_configured'] ? 'Saved securely — enter only to replace' : 'Enter SMTP password if required' }}"><small class="form-text">{{ $settings['smtp_password_configured'] ? 'A password is already stored encrypted.' : 'No SMTP password is currently stored.' }}</small><label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="clear_smtp_password" value="1" @checked($enabled('clear_smtp_password', false))><span class="form-check-label">Clear saved SMTP password</span></label></div>
            <div class="form-field"><label class="form-label" for="mail_from_address">From email address</label><input id="mail_from_address" type="email" class="form-control" name="mail_from_address" value="{{ $value('mail_from_address', config('mail.from.address', '')) }}"></div>
            <div class="form-field"><label class="form-label" for="mail_from_name">From name</label><input id="mail_from_name" class="form-control" name="mail_from_name" required maxlength="150" value="{{ $value('mail_from_name', config('mail.from.name', 'IT Inventory')) }}"></div>
        </div>
        <div class="settings-callout"><i class="bi bi-info-circle"></i><span>Save the SMTP settings before sending a test message. Outbound delivery also depends on DNS, firewall policy, provider authentication, and the server's network access.</span></div>
    </section>

    <section class="panel settings-section" id="settings-regional">
        <div class="panel-header"><div><h2>Date, time and regional configuration</h2><p>Set the operating timezone and display conventions.</p></div><span class="settings-section-number">06</span></div>
        <div class="settings-grid">
            <div class="form-field"><label class="form-label" for="currency">Currency</label><select id="currency" name="currency" class="form-select"><option value="PHP" @selected($value('currency', 'PHP') === 'PHP')>PHP — Philippine peso</option><option value="USD" @selected($value('currency') === 'USD')>USD — US dollar</option><option value="EUR" @selected($value('currency') === 'EUR')>EUR — Euro</option><option value="SGD" @selected($value('currency') === 'SGD')>SGD — Singapore dollar</option></select></div>
            <div class="form-field"><label class="form-label" for="timezone">System timezone</label><select id="timezone" name="timezone" class="form-select">@foreach(timezone_identifiers_list() as $timezone)<option value="{{ $timezone }}" @selected($value('timezone', config('app.timezone')) === $timezone)>{{ $timezone }}</option>@endforeach</select></div>
            <div class="form-field"><label class="form-label" for="date_format">Date format</label><select id="date_format" name="date_format" class="form-select"><option value="D, d M Y" @selected($value('date_format', 'D, d M Y') === 'D, d M Y')>Sat, 21 Nov 2026</option><option value="d M Y" @selected($value('date_format') === 'd M Y')>21 Nov 2026</option><option value="m/d/Y" @selected($value('date_format') === 'm/d/Y')>11/21/2026</option><option value="Y-m-d" @selected($value('date_format') === 'Y-m-d')>2026-11-21</option></select></div>
            <div class="form-field"><label class="form-label" for="time_format">Time format</label><select id="time_format" name="time_format" class="form-select"><option value="H:i" @selected($value('time_format', 'H:i') === 'H:i')>24-hour (17:30)</option><option value="h:i A" @selected($value('time_format') === 'h:i A')>12-hour (05:30 PM)</option></select></div>
        </div>
    </section>

    <section class="panel settings-section" id="settings-backup">
        <div class="panel-header"><div><h2>Backup settings</h2><p>Configure automated private database backups and retention.</p></div><span class="settings-section-number">07</span></div>
        <div class="settings-grid">
            <div class="form-field"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="automatic_backups" value="1" @checked($enabled('automatic_backups', false))><span class="form-check-label fw-semibold">Enable automatic daily backups</span></label><small class="form-text">Requires the Laravel Scheduler and a working mysqldump binary.</small></div>
            <div class="form-field"><label class="form-label" for="backup_schedule_time">Daily backup time ({{ config('app.timezone', 'Asia/Manila') }})</label><input id="backup_schedule_time" type="time" class="form-control" name="backup_schedule_time" required value="{{ $value('backup_schedule_time', '01:30') }}"></div>
            <div class="form-field"><label class="form-label" for="backup_retention_days">Backup retention (days)</label><input id="backup_retention_days" type="number" min="1" max="3650" class="form-control" name="backup_retention_days" required value="{{ $value('backup_retention_days', 30) }}"></div>
            <div class="form-field"><label class="form-label d-block">Backup management</label>@if($canManageBackups)<a class="btn btn-outline-secondary" href="{{ route('backups.index') }}"><i class="bi bi-database me-1"></i>Open Backup Management</a>@else<span class="form-text">You do not have permission to manage backup files.</span>@endif</div>
        </div>
    </section>

    <section class="panel settings-section" id="settings-data">
        <div class="panel-header"><div><h2>Data import and export settings</h2><p>Configure upload limits and bounded report export behavior.</p></div><span class="settings-section-number">08</span></div>
        <div class="settings-grid">
            <div class="form-field"><label class="form-label" for="import_max_size_mb">Maximum import file size (MB)</label><input id="import_max_size_mb" type="number" min="1" max="50" class="form-control" name="import_max_size_mb" required value="{{ $value('import_max_size_mb', 10) }}"><small class="form-text">CSV import endpoints enforce this limit. PHP upload_max_filesize and post_max_size must be at least this large.</small></div>
            <div class="form-field"><label class="form-label" for="import_max_rows">Maximum rows per import file</label><input id="import_max_rows" type="number" min="100" max="10000" class="form-control" name="import_max_rows" required value="{{ $value('import_max_rows', 1000) }}"><small class="form-text">CSV imports are rejected when they exceed this configured row count.</small></div>
            <div class="form-field"><label class="form-label" for="default_export_format">Default export format</label><select id="default_export_format" name="default_export_format" class="form-select"><option value="csv" @selected($value('default_export_format', 'xlsx') === 'csv')>CSV</option><option value="xlsx" @selected($value('default_export_format', 'xlsx') === 'xlsx')>Excel (XLSX)</option><option value="pdf" @selected($value('default_export_format') === 'pdf')>PDF</option></select></div>
            <div class="form-field"><label class="form-label" for="export_row_limit">Maximum rows per export</label><input id="export_row_limit" type="number" min="100" max="50000" class="form-control" name="export_row_limit" required value="{{ $value('export_row_limit', 50000) }}"><small class="form-text">Applies to CSV, Excel and PDF exports. PDF output is additionally capped at 1,500 rows for memory safety.</small></div>
            <div class="form-field"><label class="form-label d-block">Data transfer actions</label><div class="d-flex flex-wrap gap-2"><a href="{{ route('reports.index') }}" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>Reports and exports</a>@if(auth()->user()->hasPermission('assets.manage'))<a href="{{ route('imports.template', 'assets') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-arrow-down me-1"></i>Asset import template</a>@endif</div></div>
        </div>
    </section>

    <section class="panel settings-section" id="settings-preferences">
        <div class="panel-header"><div><h2>System preferences and approval workflow configuration</h2><p>Set default list behavior and session policy; configure workflow routing in the approval manager.</p></div><span class="settings-section-number">09</span></div>
        <div class="settings-grid">
            <div class="form-field"><label class="form-label" for="items_per_page">Inventory records per page</label><select id="items_per_page" class="form-select" name="items_per_page"><option value="10" @selected((int) $value('items_per_page', 15) === 10)>10</option><option value="15" @selected((int) $value('items_per_page', 15) === 15)>15</option><option value="25" @selected((int) $value('items_per_page', 15) === 25)>25</option><option value="50" @selected((int) $value('items_per_page', 15) === 50)>50</option><option value="100" @selected((int) $value('items_per_page', 15) === 100)>100</option></select></div>
            <div class="form-field"><label class="form-label" for="session_timeout_minutes">Session timeout (minutes)</label><input id="session_timeout_minutes" type="number" min="15" max="720" class="form-control" name="session_timeout_minutes" required value="{{ $value('session_timeout_minutes', 120) }}"><small class="form-text">Applied to Laravel sessions. Users may need to sign in again after the configured idle period.</small></div>
            <div class="form-field"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="approval_escalation_enabled" value="1" @checked($enabled('approval_escalation_enabled', true))><span class="form-check-label fw-semibold">Enable overdue approval escalation notifications</span></label><small class="form-text">The approval scheduler must be active. Escalation thresholds are still configured per approval step.</small></div>
            <div class="form-field"><label class="form-label d-block">Approval workflow manager</label>@if($canManageApprovals)<a href="{{ route('admin.approval-flows') }}" class="btn btn-outline-secondary"><i class="bi bi-diagram-2 me-1"></i>Configure approval workflows</a>@else<span class="form-text">You do not have permission to manage approval workflows.</span>@endif</div>
        </div>
    </section>

    <div class="settings-savebar"><div><strong>System configuration</strong><span>Changes are recorded in the audit trail. Existing asset IDs and historical records are not rewritten.</span></div><button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Save all settings</button></div>
</form>

<section class="panel settings-test-mail mt-3">
    <div class="panel-header"><div><h2>Test email configuration</h2><p>Save SMTP settings above first, then send a test message to validate the configured transport.</p></div></div>
    <form method="POST" action="{{ route('settings.test-email') }}" class="settings-test-mail-form">@csrf
        <div class="form-field"><label class="form-label" for="test_email">Test recipient email</label><input id="test_email" type="email" name="test_email" class="form-control" required value="{{ old('test_email', auth()->user()->email) }}"></div>
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-send me-1"></i>Send test email</button>
    </form>
</section>
<script>
document.addEventListener('DOMContentLoaded', () => {
    ['brand_primary', 'brand_accent'].forEach((field) => {
        const picker = document.getElementById(field);
        const display = document.getElementById(`${field}_hex`);
        if (!picker || !display) return;
        const sync = () => { display.value = picker.value.toUpperCase(); };
        picker.addEventListener('input', sync);
        sync();
    });
});
</script>

@push('scripts')
<script>
(() => {
    const input = document.getElementById('system_logo');
    const box = document.getElementById('systemLogoPreview');
    if (!input || !box) return;
    let objectUrl = null;
    input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (!file) return;
        if (!['image/png', 'image/jpeg', 'image/webp'].includes(file.type)) {
            input.value = '';
            return;
        }
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(file);
        let image = box.querySelector('img');
        if (!image) {
            image = document.createElement('img');
            box.replaceChildren(image);
        }
        const placeholder = box.querySelector('#systemLogoPreviewPlaceholder');
        if (placeholder) placeholder.hidden = true;
        image.src = objectUrl;
        image.alt = 'Selected system logo preview';
        image.hidden = false;
    });
    window.addEventListener('beforeunload', () => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
    });
})();
</script>
@endpush
@endsection
