<?php $__env->startSection('title', 'System Settings'); ?>
<?php $__env->startSection('breadcrumb', 'System Settings'); ?>

<?php $__env->startSection('content'); ?>
<?php
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
?>

<div class="page-heading">
    <div>
        <div class="eyebrow">ADMINISTRATION</div>
        <h1>System Settings and Configuration</h1>
        <p>Manage the organization profile, inventory rules, integrations, and system-wide preferences.</p>
    </div>
    <div class="heading-actions">
        <a href="<?php echo e(route('mfa.settings')); ?>" class="btn btn-outline-secondary"><i class="bi bi-shield-lock me-1"></i>Account MFA</a>
        <button form="systemSettingsForm" type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Save settings</button>
    </div>
</div>

<?php if($errors->any()): ?>
    <div class="alert alert-danger"><strong>Please review the highlighted settings.</strong><ul class="mb-0 mt-2"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul></div>
<?php endif; ?>

<nav class="settings-index" aria-label="Settings sections">
    <?php $__currentLoopData = $navLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$id, $icon, $label]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="#settings-<?php echo e($id); ?>"><i class="bi <?php echo e($icon); ?>"></i><span><?php echo e($label); ?></span></a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</nav>

<form id="systemSettingsForm" method="POST" action="<?php echo e(route('settings.update')); ?>" enctype="multipart/form-data">
    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>

    <section class="panel settings-section" id="settings-organization">
        <div class="panel-header"><div><h2>Organization profile and system branding</h2><p>Identity displayed in the application shell, sign-in page, and supported reports.</p></div><span class="settings-section-number">01</span></div>
        <div class="settings-grid">
            <div class="form-field"><label class="form-label" for="organization_name">Organization name</label><input id="organization_name" class="form-control" name="organization_name" required maxlength="150" value="<?php echo e($value('organization_name', config('app.name'))); ?>"></div>
            <div class="form-field"><label class="form-label" for="system_name">System name</label><input id="system_name" class="form-control" name="system_name" required maxlength="120" value="<?php echo e($value('system_name', config('app.name', 'IT Inventory Management System'))); ?>"></div>
            <div class="form-field"><label class="form-label" for="system_short_name">System short name</label><input id="system_short_name" class="form-control" name="system_short_name" required maxlength="40" value="<?php echo e($value('system_short_name', 'IT Inventory')); ?>"><small class="form-text">Displayed in the sidebar brand area.</small></div>
            <div class="form-field"><label class="form-label" for="organization_email">Organization contact email</label><input id="organization_email" type="email" class="form-control" name="organization_email" maxlength="150" value="<?php echo e($value('organization_email')); ?>"></div>
            <div class="form-field"><label class="form-label" for="organization_phone">Organization contact number</label><input id="organization_phone" class="form-control" name="organization_phone" maxlength="50" value="<?php echo e($value('organization_phone')); ?>"></div>
            <div class="form-field field-full"><label class="form-label" for="organization_address">Organization address</label><textarea id="organization_address" class="form-control" name="organization_address" rows="2" maxlength="500"><?php echo e($value('organization_address')); ?></textarea></div>
            <div class="form-field"><label class="form-label" for="system_logo">System logo</label><input id="system_logo" type="file" class="form-control" name="system_logo" accept="image/png,image/jpeg,image/webp"><small class="form-text">PNG, JPG or WebP; maximum 2 MB. Use a transparent logo for best results.</small></div>
            <div class="form-field"><label class="form-label d-block">Current logo</label><div class="settings-logo-preview" id="systemLogoPreview"><?php if($settings['system_logo_url'] ?? false): ?><img id="systemLogoPreviewImage" src="<?php echo e($settings['system_logo_url']); ?>" alt="Current system logo"><?php elseif($settings['system_logo_missing'] ?? false): ?><span class="text-warning small" id="systemLogoPreviewPlaceholder">The saved logo file is missing. Upload the logo again.</span><?php else: ?><img id="systemLogoPreviewImage" alt="Selected system logo preview" hidden><span class="text-secondary small" id="systemLogoPreviewPlaceholder">No custom logo uploaded</span><?php endif; ?></div><label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_system_logo" value="1" <?php if($enabled('remove_system_logo', false)): echo 'checked'; endif; ?>><span class="form-check-label">Remove current logo</span></label></div>
            <div class="form-field"><label class="form-label" for="brand_primary">Primary brand color</label><div class="d-flex align-items-center gap-2"><input id="brand_primary" type="color" class="form-control form-control-color" name="brand_primary" value="<?php echo e($value('brand_primary', '#315f9f')); ?>"><input id="brand_primary_hex" class="form-control" value="<?php echo e(strtoupper($value('brand_primary', '#315f9f'))); ?>" readonly aria-label="Primary color hex value"></div></div>
            <div class="form-field"><label class="form-label" for="brand_accent">Accent brand color</label><div class="d-flex align-items-center gap-2"><input id="brand_accent" type="color" class="form-control form-control-color" name="brand_accent" value="<?php echo e($value('brand_accent', '#0caa6a')); ?>"><input id="brand_accent_hex" class="form-control" value="<?php echo e(strtoupper($value('brand_accent', '#0caa6a'))); ?>" readonly aria-label="Accent color hex value"></div></div>
        </div>
    </section>

    <section class="panel settings-section" id="settings-configuration">
        <div class="panel-header"><div><h2>Department, location and reference data configuration</h2><p>Use the dedicated registers to maintain organizational reference records.</p></div><span class="settings-section-number">02</span></div>
        <div class="settings-link-grid">
            <?php if($canManageDepartments): ?><a class="settings-link-card" href="<?php echo e(route('inventory.index', 'departments')); ?>"><span class="settings-link-icon"><i class="bi bi-diagram-3"></i></span><span><strong>Department configuration</strong><small>Department codes, hierarchy and department heads</small></span><i class="bi bi-arrow-up-right"></i></a><?php endif; ?>
            <?php if($canManageLocations): ?><a class="settings-link-card" href="<?php echo e(route('inventory.index', 'locations')); ?>"><span class="settings-link-icon"><i class="bi bi-geo-alt"></i></span><span><strong>Location configuration</strong><small>Buildings, floors, rooms, storage and data centers</small></span><i class="bi bi-arrow-up-right"></i></a><?php endif; ?>
            <?php if($canManageCategories): ?><a class="settings-link-card" href="<?php echo e(route('inventory.index', 'categories')); ?>"><span class="settings-link-icon"><i class="bi bi-tags"></i></span><span><strong>Asset category configuration</strong><small>Category type, useful life and depreciation rules</small></span><i class="bi bi-arrow-up-right"></i></a><?php endif; ?>
        </div>
        <div class="settings-grid settings-reference-grid">
            <div class="form-field field-full"><label class="form-label">Asset status labels</label><p class="form-text mt-0">Labels can be customized, but the underlying status codes remain fixed to protect workflow rules and historical data.</p>
                <div class="settings-inline-fields">
                    <?php $__currentLoopData = $statusLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $defaultLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div><label class="form-label" for="asset_status_label_<?php echo e($key); ?>"><?php echo e(ucfirst(str_replace('_', ' ', $key))); ?></label><input id="asset_status_label_<?php echo e($key); ?>" class="form-control" name="asset_status_label_<?php echo e($key); ?>" required maxlength="60" value="<?php echo e($value('asset_status_label_'.$key, $defaultLabel)); ?>"></div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            <div class="form-field field-full"><label class="form-label">Asset condition labels</label>
                <div class="settings-inline-fields settings-condition-fields">
                    <?php $__currentLoopData = $conditionLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $defaultLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div><label class="form-label" for="asset_condition_label_<?php echo e(strtolower($key)); ?>"><?php echo e($key); ?></label><input id="asset_condition_label_<?php echo e(strtolower($key)); ?>" class="form-control" name="asset_condition_label_<?php echo e(strtolower($key)); ?>" required maxlength="60" value="<?php echo e($value('asset_condition_label_'.strtolower($key), $defaultLabel)); ?>"></div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            <div class="form-field field-full"><label class="form-label" for="inventory_units">Inventory unit configuration</label><textarea id="inventory_units" name="inventory_units" class="form-control" rows="4" required><?php echo e(old('inventory_units', $settings['inventory_units_text'])); ?></textarea><small class="form-text">One unit per line; maximum 40 values. These options are used when creating or editing consumable stock records.</small></div>
        </div>
    </section>

    <section class="panel settings-section" id="settings-assets">
        <div class="panel-header"><div><h2>Asset identification and depreciation settings</h2><p>Control generated asset tags and default valuation policy for new records.</p></div><span class="settings-section-number">03</span></div>
        <div class="settings-grid">
            <div class="form-field"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="automatic_asset_numbering" value="1" <?php if($enabled('automatic_asset_numbering', true)): echo 'checked'; endif; ?>><span class="form-check-label fw-semibold">Enable automatic asset numbering</span></label><small class="form-text">When disabled, users must enter an asset ID manually.</small></div>
            <div class="form-field"><label class="form-label" for="asset_tag_prefix">Asset ID prefix</label><input id="asset_tag_prefix" class="form-control" name="asset_tag_prefix" required maxlength="12" pattern="[A-Za-z0-9][A-Za-z0-9_-]*" value="<?php echo e($value('asset_tag_prefix', 'IT')); ?>"><small class="form-text">New automatically generated IDs use PREFIX-YEAR-SEQUENCE. Existing tags are not changed.</small></div>
            <div class="form-field"><label class="form-label" for="default_useful_life_months">Default useful life (months)</label><input id="default_useful_life_months" type="number" min="1" max="600" class="form-control" name="default_useful_life_months" required value="<?php echo e($value('default_useful_life_months', 48)); ?>"></div>
            <div class="form-field"><label class="form-label" for="default_depreciation_method">Default depreciation method</label><select id="default_depreciation_method" class="form-select" name="default_depreciation_method"><option value="straight_line" <?php if($value('default_depreciation_method', 'straight_line') === 'straight_line'): echo 'selected'; endif; ?>>Straight line</option><option value="not_configured" <?php if($value('default_depreciation_method', 'not_configured') === 'not_configured'): echo 'selected'; endif; ?>>Not configured</option></select></div>
            <div class="form-field"><label class="form-label" for="depreciation_start_policy">Depreciation start convention</label><select id="depreciation_start_policy" class="form-select" name="depreciation_start_policy"><option value="next_month" <?php if($value('depreciation_start_policy', 'next_month') === 'next_month'): echo 'selected'; endif; ?>>Month after acquisition</option><option value="purchase_month" <?php if($value('depreciation_start_policy', 'next_month') === 'purchase_month'): echo 'selected'; endif; ?>>Acquisition month</option></select></div>
            <div class="form-field"><label class="form-label" for="default_salvage_percent">Default salvage value (%)</label><input id="default_salvage_percent" type="number" min="0" max="100" step="0.001" class="form-control" name="default_salvage_percent" value="<?php echo e($value('default_salvage_percent', 0)); ?>" required><small class="form-text">Applied as a default for new asset valuation records where applicable.</small></div>
        </div>
    </section>

    <section class="panel settings-section" id="settings-notifications">
        <div class="panel-header"><div><h2>Notification and maintenance scheduling settings</h2><p>Configure alert channels, reminder windows, and preventive schedule generation.</p></div><span class="settings-section-number">04</span></div>
        <div class="settings-grid">
            <div class="form-field"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="notifications_enabled" value="1" <?php if($enabled('notifications_enabled', true)): echo 'checked'; endif; ?>><span class="form-check-label fw-semibold">Enable scheduled inventory reminder notifications</span></label></div>
            <div class="form-field"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="notify_low_stock" value="1" <?php if($enabled('notify_low_stock', true)): echo 'checked'; endif; ?>><span class="form-check-label fw-semibold">Low-stock alerts</span></label><label class="form-label mt-2" for="low_stock_default">Default reorder threshold</label><input id="low_stock_default" type="number" min="0" max="1000000" class="form-control" name="low_stock_default" required value="<?php echo e($value('low_stock_default', 5)); ?>"></div>
            <div class="form-field"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="notify_warranty" value="1" <?php if($enabled('notify_warranty', true)): echo 'checked'; endif; ?>><span class="form-check-label fw-semibold">Warranty expiration alerts</span></label><label class="form-label mt-2" for="warranty_reminder_days">Warranty reminder window (days)</label><input id="warranty_reminder_days" type="number" min="1" max="365" class="form-control" name="warranty_reminder_days" required value="<?php echo e($value('warranty_reminder_days', 30)); ?>"></div>
            <div class="form-field"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="notify_maintenance" value="1" <?php if($enabled('notify_maintenance', true)): echo 'checked'; endif; ?>><span class="form-check-label fw-semibold">Maintenance due alerts</span></label><label class="form-label mt-2" for="maintenance_reminder_days">Maintenance reminder window (days)</label><input id="maintenance_reminder_days" type="number" min="1" max="365" class="form-control" name="maintenance_reminder_days" required value="<?php echo e($value('maintenance_reminder_days', 14)); ?>"></div>
            <div class="form-field"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="notify_license_expiry" value="1" <?php if($enabled('notify_license_expiry', true)): echo 'checked'; endif; ?>><span class="form-check-label fw-semibold">Software license expiration alerts</span></label><label class="form-label mt-2" for="license_reminder_days">License reminder window (days)</label><input id="license_reminder_days" type="number" min="1" max="365" class="form-control" name="license_reminder_days" required value="<?php echo e($value('license_reminder_days', 60)); ?>"></div>
            <div class="form-field"><label class="form-label" for="maintenance_generation_time">Preventive maintenance generation time</label><input id="maintenance_generation_time" type="time" class="form-control" name="maintenance_generation_time" required value="<?php echo e($value('maintenance_generation_time', '06:00')); ?>"><small class="form-text">The Laravel Scheduler must be running on the server for this schedule to execute.</small></div>
            <div class="form-field"><label class="form-label" for="notification_delivery_time">Daily inventory alert delivery time</label><input id="notification_delivery_time" type="time" class="form-control" name="notification_delivery_time" required value="<?php echo e($value('notification_delivery_time', '08:00')); ?>"><small class="form-text">Uses the configured system timezone and the Laravel Scheduler.</small></div>
        </div>
    </section>

    <section class="panel settings-section" id="settings-email">
        <div class="panel-header"><div><h2>Email SMTP configuration</h2><p>SMTP credentials are encrypted before they are stored. The password is never displayed after saving.</p></div><span class="settings-section-number">05</span></div>
        <div class="settings-grid">
            <div class="form-field"><label class="form-label" for="smtp_mailer">Mail transport</label><select id="smtp_mailer" class="form-select" name="smtp_mailer"><option value="smtp" <?php if($value('smtp_mailer', config('mail.default', 'log')) === 'smtp'): echo 'selected'; endif; ?>>SMTP server</option><option value="log" <?php if($value('smtp_mailer', config('mail.default', 'log')) === 'log'): echo 'selected'; endif; ?>>Log only (no outbound email)</option></select></div>
            <div class="form-field"><label class="form-label" for="smtp_host">SMTP host</label><input id="smtp_host" class="form-control" name="smtp_host" maxlength="190" value="<?php echo e($value('smtp_host', config('mail.mailers.smtp.host', ''))); ?>" placeholder="smtp.example.com"></div>
            <div class="form-field"><label class="form-label" for="smtp_port">SMTP port</label><input id="smtp_port" type="number" min="1" max="65535" class="form-control" name="smtp_port" value="<?php echo e($value('smtp_port', config('mail.mailers.smtp.port', 587))); ?>"></div>
            <div class="form-field"><label class="form-label" for="smtp_encryption">SMTP encryption</label><select id="smtp_encryption" name="smtp_encryption" class="form-select"><option value="tls" <?php if($value('smtp_encryption', 'tls') === 'tls'): echo 'selected'; endif; ?>>STARTTLS / TLS</option><option value="ssl" <?php if($value('smtp_encryption', 'tls') === 'ssl'): echo 'selected'; endif; ?>>Implicit TLS / SSL</option><option value="none" <?php if($value('smtp_encryption', 'tls') === 'none'): echo 'selected'; endif; ?>>None</option></select></div>
            <div class="form-field"><label class="form-label" for="smtp_username">SMTP username</label><input id="smtp_username" class="form-control" name="smtp_username" maxlength="190" autocomplete="off" value="<?php echo e($value('smtp_username')); ?>"></div>
            <div class="form-field"><label class="form-label" for="smtp_password">SMTP password</label><input id="smtp_password" type="password" class="form-control" name="smtp_password" maxlength="500" autocomplete="new-password" placeholder="<?php echo e($settings['smtp_password_configured'] ? 'Saved securely — enter only to replace' : 'Enter SMTP password if required'); ?>"><small class="form-text"><?php echo e($settings['smtp_password_configured'] ? 'A password is already stored encrypted.' : 'No SMTP password is currently stored.'); ?></small><label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="clear_smtp_password" value="1" <?php if($enabled('clear_smtp_password', false)): echo 'checked'; endif; ?>><span class="form-check-label">Clear saved SMTP password</span></label></div>
            <div class="form-field"><label class="form-label" for="mail_from_address">From email address</label><input id="mail_from_address" type="email" class="form-control" name="mail_from_address" value="<?php echo e($value('mail_from_address', config('mail.from.address', ''))); ?>"></div>
            <div class="form-field"><label class="form-label" for="mail_from_name">From name</label><input id="mail_from_name" class="form-control" name="mail_from_name" required maxlength="150" value="<?php echo e($value('mail_from_name', config('mail.from.name', 'IT Inventory'))); ?>"></div>
        </div>
        <div class="settings-callout"><i class="bi bi-info-circle"></i><span>Save the SMTP settings before sending a test message. Outbound delivery also depends on DNS, firewall policy, provider authentication, and the server's network access.</span></div>
    </section>

    <section class="panel settings-section" id="settings-regional">
        <div class="panel-header"><div><h2>Date, time and regional configuration</h2><p>Set the operating timezone and display conventions.</p></div><span class="settings-section-number">06</span></div>
        <div class="settings-grid">
            <div class="form-field"><label class="form-label" for="currency">Currency</label><select id="currency" name="currency" class="form-select"><option value="PHP" <?php if($value('currency', 'PHP') === 'PHP'): echo 'selected'; endif; ?>>PHP — Philippine peso</option><option value="USD" <?php if($value('currency') === 'USD'): echo 'selected'; endif; ?>>USD — US dollar</option><option value="EUR" <?php if($value('currency') === 'EUR'): echo 'selected'; endif; ?>>EUR — Euro</option><option value="SGD" <?php if($value('currency') === 'SGD'): echo 'selected'; endif; ?>>SGD — Singapore dollar</option></select></div>
            <div class="form-field"><label class="form-label" for="timezone">System timezone</label><select id="timezone" name="timezone" class="form-select"><?php $__currentLoopData = timezone_identifiers_list(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $timezone): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($timezone); ?>" <?php if($value('timezone', config('app.timezone')) === $timezone): echo 'selected'; endif; ?>><?php echo e($timezone); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
            <div class="form-field"><label class="form-label" for="date_format">Date format</label><select id="date_format" name="date_format" class="form-select"><option value="D, d M Y" <?php if($value('date_format', 'D, d M Y') === 'D, d M Y'): echo 'selected'; endif; ?>>Sat, 21 Nov 2026</option><option value="d M Y" <?php if($value('date_format') === 'd M Y'): echo 'selected'; endif; ?>>21 Nov 2026</option><option value="m/d/Y" <?php if($value('date_format') === 'm/d/Y'): echo 'selected'; endif; ?>>11/21/2026</option><option value="Y-m-d" <?php if($value('date_format') === 'Y-m-d'): echo 'selected'; endif; ?>>2026-11-21</option></select></div>
            <div class="form-field"><label class="form-label" for="time_format">Time format</label><select id="time_format" name="time_format" class="form-select"><option value="H:i" <?php if($value('time_format', 'H:i') === 'H:i'): echo 'selected'; endif; ?>>24-hour (17:30)</option><option value="h:i A" <?php if($value('time_format') === 'h:i A'): echo 'selected'; endif; ?>>12-hour (05:30 PM)</option></select></div>
        </div>
    </section>

    <section class="panel settings-section" id="settings-backup">
        <div class="panel-header"><div><h2>Backup settings</h2><p>Configure automated private database backups and retention.</p></div><span class="settings-section-number">07</span></div>
        <div class="settings-grid">
            <div class="form-field"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="automatic_backups" value="1" <?php if($enabled('automatic_backups', false)): echo 'checked'; endif; ?>><span class="form-check-label fw-semibold">Enable automatic daily backups</span></label><small class="form-text">Requires the Laravel Scheduler and a working mysqldump binary.</small></div>
            <div class="form-field"><label class="form-label" for="backup_schedule_time">Daily backup time (<?php echo e(config('app.timezone', 'Asia/Manila')); ?>)</label><input id="backup_schedule_time" type="time" class="form-control" name="backup_schedule_time" required value="<?php echo e($value('backup_schedule_time', '01:30')); ?>"></div>
            <div class="form-field"><label class="form-label" for="backup_retention_days">Backup retention (days)</label><input id="backup_retention_days" type="number" min="1" max="3650" class="form-control" name="backup_retention_days" required value="<?php echo e($value('backup_retention_days', 30)); ?>"></div>
            <div class="form-field"><label class="form-label d-block">Backup management</label><?php if($canManageBackups): ?><a class="btn btn-outline-secondary" href="<?php echo e(route('backups.index')); ?>"><i class="bi bi-database me-1"></i>Open Backup Management</a><?php else: ?><span class="form-text">You do not have permission to manage backup files.</span><?php endif; ?></div>
        </div>
    </section>

    <section class="panel settings-section" id="settings-data">
        <div class="panel-header"><div><h2>Data import and export settings</h2><p>Configure upload limits and bounded report export behavior.</p></div><span class="settings-section-number">08</span></div>
        <div class="settings-grid">
            <div class="form-field"><label class="form-label" for="import_max_size_mb">Maximum import file size (MB)</label><input id="import_max_size_mb" type="number" min="1" max="50" class="form-control" name="import_max_size_mb" required value="<?php echo e($value('import_max_size_mb', 10)); ?>"><small class="form-text">CSV import endpoints enforce this limit. PHP upload_max_filesize and post_max_size must be at least this large.</small></div>
            <div class="form-field"><label class="form-label" for="import_max_rows">Maximum rows per import file</label><input id="import_max_rows" type="number" min="100" max="10000" class="form-control" name="import_max_rows" required value="<?php echo e($value('import_max_rows', 1000)); ?>"><small class="form-text">CSV imports are rejected when they exceed this configured row count.</small></div>
            <div class="form-field"><label class="form-label" for="default_export_format">Default export format</label><select id="default_export_format" name="default_export_format" class="form-select"><option value="csv" <?php if($value('default_export_format', 'xlsx') === 'csv'): echo 'selected'; endif; ?>>CSV</option><option value="xlsx" <?php if($value('default_export_format', 'xlsx') === 'xlsx'): echo 'selected'; endif; ?>>Excel (XLSX)</option><option value="pdf" <?php if($value('default_export_format') === 'pdf'): echo 'selected'; endif; ?>>PDF</option></select></div>
            <div class="form-field"><label class="form-label" for="export_row_limit">Maximum rows per export</label><input id="export_row_limit" type="number" min="100" max="50000" class="form-control" name="export_row_limit" required value="<?php echo e($value('export_row_limit', 50000)); ?>"><small class="form-text">Applies to CSV, Excel and PDF exports. PDF output is additionally capped at 1,500 rows for memory safety.</small></div>
            <div class="form-field"><label class="form-label d-block">Data transfer actions</label><div class="d-flex flex-wrap gap-2"><a href="<?php echo e(route('reports.index')); ?>" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>Reports and exports</a><?php if(auth()->user()->hasPermission('assets.manage')): ?><a href="<?php echo e(route('imports.template', 'assets')); ?>" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-arrow-down me-1"></i>Asset import template</a><?php endif; ?></div></div>
        </div>
    </section>

    <section class="panel settings-section" id="settings-preferences">
        <div class="panel-header"><div><h2>System preferences and approval workflow configuration</h2><p>Set default list behavior and session policy; configure workflow routing in the approval manager.</p></div><span class="settings-section-number">09</span></div>
        <div class="settings-grid">
            <div class="form-field"><label class="form-label" for="items_per_page">Inventory records per page</label><select id="items_per_page" class="form-select" name="items_per_page"><option value="10" <?php if((int) $value('items_per_page', 15) === 10): echo 'selected'; endif; ?>>10</option><option value="15" <?php if((int) $value('items_per_page', 15) === 15): echo 'selected'; endif; ?>>15</option><option value="25" <?php if((int) $value('items_per_page', 15) === 25): echo 'selected'; endif; ?>>25</option><option value="50" <?php if((int) $value('items_per_page', 15) === 50): echo 'selected'; endif; ?>>50</option><option value="100" <?php if((int) $value('items_per_page', 15) === 100): echo 'selected'; endif; ?>>100</option></select></div>
            <div class="form-field"><label class="form-label" for="session_timeout_minutes">Session timeout (minutes)</label><input id="session_timeout_minutes" type="number" min="15" max="720" class="form-control" name="session_timeout_minutes" required value="<?php echo e($value('session_timeout_minutes', 120)); ?>"><small class="form-text">Applied to Laravel sessions. Users may need to sign in again after the configured idle period.</small></div>
            <div class="form-field"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="approval_escalation_enabled" value="1" <?php if($enabled('approval_escalation_enabled', true)): echo 'checked'; endif; ?>><span class="form-check-label fw-semibold">Enable overdue approval escalation notifications</span></label><small class="form-text">The approval scheduler must be active. Escalation thresholds are still configured per approval step.</small></div>
            <div class="form-field"><label class="form-label d-block">Approval workflow manager</label><?php if($canManageApprovals): ?><a href="<?php echo e(route('admin.approval-flows')); ?>" class="btn btn-outline-secondary"><i class="bi bi-diagram-2 me-1"></i>Configure approval workflows</a><?php else: ?><span class="form-text">You do not have permission to manage approval workflows.</span><?php endif; ?></div>
        </div>
    </section>

    <div class="settings-savebar"><div><strong>System configuration</strong><span>Changes are recorded in the audit trail. Existing asset IDs and historical records are not rewritten.</span></div><button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Save all settings</button></div>
</form>

<section class="panel settings-test-mail mt-3">
    <div class="panel-header"><div><h2>Test email configuration</h2><p>Save SMTP settings above first, then send a test message to validate the configured transport.</p></div></div>
    <form method="POST" action="<?php echo e(route('settings.test-email')); ?>" class="settings-test-mail-form"><?php echo csrf_field(); ?>
        <div class="form-field"><label class="form-label" for="test_email">Test recipient email</label><input id="test_email" type="email" name="test_email" class="form-control" required value="<?php echo e(old('test_email', auth()->user()->email)); ?>"></div>
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

<?php $__env->startPush('scripts'); ?>
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
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\it-inventory\resources\views/admin/settings.blade.php ENDPATH**/ ?>