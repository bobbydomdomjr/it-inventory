<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class SettingsController extends Controller
{
    private const STATUS_LABELS = [
        'available' => 'Available', 'assigned' => 'Assigned', 'maintenance' => 'Maintenance',
        'defective' => 'Defective', 'lost' => 'Lost / Missing', 'retired' => 'Retired',
    ];

    private const CONDITION_LABELS = [
        'New' => 'New', 'Good' => 'Good', 'Fair' => 'Fair', 'Defective' => 'Defective',
    ];

    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);

        $records = SystemSetting::query()->get()->keyBy('key');
        $settings = $records->mapWithKeys(fn (SystemSetting $record) => [
            $record->key => is_array($record->value) && array_key_exists('value', $record->value)
                ? $record->value['value']
                : $record->value,
        ])->all();

        $settings['smtp_password_configured'] = filled($settings['smtp_password_encrypted'] ?? null);
        unset($settings['smtp_password_encrypted']);
        $settings['inventory_units_text'] = implode("\n", (array) ($settings['inventory_units'] ?? ['piece', 'unit', 'box', 'pack', 'set']));

        // Serve branding files through the application so the logo works even when
        // the hosting environment has not created Laravel's public/storage symlink.
        $logoPath = $settings['organization_logo_path'] ?? null;
        $logoPathIsValid = is_string($logoPath)
            && preg_match('#\Abranding/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:png|jpe?g|webp)\z#i', $logoPath) === 1;
        $logoExists = $logoPathIsValid && Storage::disk('public')->exists($logoPath);
        $settings['system_logo_url'] = $logoExists
            ? route('branding.logo', ['v' => substr(hash('sha256', $logoPath), 0, 12)])
            : null;
        $settings['system_logo_missing'] = filled($logoPath) && ! $logoExists;

        return view('admin.settings', [
            'settings' => $settings,
            'statusLabels' => self::STATUS_LABELS,
            'conditionLabels' => self::CONDITION_LABELS,
            'canManageDepartments' => $request->user()->hasPermission('departments.manage'),
            'canManageLocations' => $request->user()->hasPermission('locations.manage'),
            'canManageCategories' => $request->user()->hasPermission('categories.manage'),
            'canManageApprovals' => $request->user()->hasPermission('approvals.manage'),
            'canManageBackups' => $request->user()->hasPermission('backups.manage'),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);

        foreach ([
            'automatic_asset_numbering', 'notifications_enabled', 'notify_low_stock',
            'notify_warranty', 'notify_maintenance', 'notify_license_expiry',
            'approval_escalation_enabled', 'automatic_backups', 'clear_smtp_password',
            'remove_system_logo',
        ] as $field) {
            $request->merge([$field => $request->boolean($field)]);
        }

        $rules = [
            'organization_name' => 'required|string|max:150',
            'system_name' => 'required|string|max:120',
            'system_short_name' => 'required|string|max:40',
            'organization_address' => 'nullable|string|max:500',
            'organization_email' => 'nullable|email|max:150',
            'organization_phone' => 'nullable|string|max:50',
            'system_logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'remove_system_logo' => 'required|boolean',
            'brand_primary' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_accent' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'currency' => 'required|in:PHP,USD,EUR,SGD',
            'timezone' => ['required', Rule::in(timezone_identifiers_list())],
            'date_format' => ['required', Rule::in(['D, d M Y', 'd M Y', 'm/d/Y', 'Y-m-d'])],
            'time_format' => 'required|in:H:i,h:i A',
            'automatic_asset_numbering' => 'required|boolean',
            'asset_tag_prefix' => ['required', 'string', 'max:12', 'regex:/^[A-Za-z0-9][A-Za-z0-9_-]*$/'],
            'default_useful_life_months' => 'required|integer|min:1|max:600',
            'default_depreciation_method' => 'required|in:straight_line,not_configured',
            'depreciation_start_policy' => 'required|in:next_month,purchase_month',
            'default_salvage_percent' => 'required|numeric|min:0|max:100',
            'low_stock_default' => 'required|integer|min:0|max:1000000',
            'maintenance_reminder_days' => 'required|integer|min:1|max:365',
            'warranty_reminder_days' => 'required|integer|min:1|max:365',
            'license_reminder_days' => 'required|integer|min:1|max:365',
            'notifications_enabled' => 'required|boolean',
            'notify_low_stock' => 'required|boolean',
            'notify_warranty' => 'required|boolean',
            'notify_maintenance' => 'required|boolean',
            'notify_license_expiry' => 'required|boolean',
            'maintenance_generation_time' => 'required|date_format:H:i',
            'notification_delivery_time' => 'required|date_format:H:i',
            'smtp_mailer' => 'required|in:smtp,log',
            'smtp_host' => 'nullable|required_if:smtp_mailer,smtp|string|max:190',
            'smtp_port' => 'nullable|required_if:smtp_mailer,smtp|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:190',
            'smtp_password' => 'nullable|string|max:500',
            'clear_smtp_password' => 'required|boolean',
            'smtp_encryption' => 'required|in:none,tls,ssl',
            'mail_from_address' => 'nullable|required_if:smtp_mailer,smtp|email|max:190',
            'mail_from_name' => 'required|string|max:150',
            'backup_schedule_time' => 'required|date_format:H:i',
            'automatic_backups' => 'required|boolean',
            'backup_retention_days' => 'required|integer|min:1|max:3650',
            'import_max_size_mb' => 'required|integer|min:1|max:50',
            'import_max_rows' => 'required|integer|min:100|max:10000',
            'export_row_limit' => 'required|integer|min:100|max:50000',
            'default_export_format' => 'required|in:csv,xlsx,pdf',
            'inventory_units' => 'required|string|max:1000',
            'items_per_page' => 'required|integer|in:10,15,25,50,100',
            'session_timeout_minutes' => 'required|integer|min:15|max:720',
            'approval_escalation_enabled' => 'required|boolean',
        ];

        foreach (array_keys(self::STATUS_LABELS) as $status) {
            $rules['asset_status_label_'.$status] = 'required|string|max:60';
        }
        foreach (array_keys(self::CONDITION_LABELS) as $condition) {
            $rules['asset_condition_label_'.strtolower($condition)] = 'required|string|max:60';
        }

        $data = $request->validate($rules);
        $units = collect(preg_split('/\r\n|\r|\n/', $data['inventory_units']))
            ->map(fn ($unit) => trim($unit))
            ->filter()
            ->unique(fn ($unit) => strtolower($unit))
            ->values();
        abort_if($units->isEmpty() || $units->count() > 40 || $units->contains(fn ($unit) => strlen($unit) > 30), 422, 'Enter between 1 and 40 inventory units. Each unit must be 30 characters or fewer.');

        $oldLogoRecord = SystemSetting::where('key', 'organization_logo_path')->first();
        $oldLogoPath = data_get($oldLogoRecord?->value, 'value');
        $newLogoPath = $oldLogoPath;
        if ($request->hasFile('system_logo')) {
            $newLogoPath = $request->file('system_logo')->store('branding', 'public');
        } elseif ($data['remove_system_logo']) {
            $newLogoPath = null;
        }

        $settingsToSave = [
            'organization_name' => [$data['organization_name'], 'organization'],
            'system_name' => [$data['system_name'], 'branding'],
            'system_short_name' => [$data['system_short_name'], 'branding'],
            'organization_address' => [$data['organization_address'] ?? '', 'organization'],
            'organization_email' => [$data['organization_email'] ?? '', 'organization'],
            'organization_phone' => [$data['organization_phone'] ?? '', 'organization'],
            'organization_logo_path' => [$newLogoPath, 'branding'],
            'brand_primary' => [$data['brand_primary'], 'branding'],
            'brand_accent' => [$data['brand_accent'], 'branding'],
            'currency' => [$data['currency'], 'regional'],
            'timezone' => [$data['timezone'], 'regional'],
            'date_format' => [$data['date_format'], 'regional'],
            'time_format' => [$data['time_format'], 'regional'],
            'automatic_asset_numbering' => [$data['automatic_asset_numbering'], 'assets'],
            'asset_tag_prefix' => [strtoupper($data['asset_tag_prefix']), 'assets'],
            'default_useful_life_months' => [(int) $data['default_useful_life_months'], 'depreciation'],
            'default_depreciation_method' => [$data['default_depreciation_method'], 'depreciation'],
            'depreciation_start_policy' => [$data['depreciation_start_policy'], 'depreciation'],
            'default_salvage_percent' => [(float) $data['default_salvage_percent'], 'depreciation'],
            'low_stock_default' => [(int) $data['low_stock_default'], 'notifications'],
            'maintenance_reminder_days' => [(int) $data['maintenance_reminder_days'], 'notifications'],
            'warranty_reminder_days' => [(int) $data['warranty_reminder_days'], 'notifications'],
            'license_reminder_days' => [(int) $data['license_reminder_days'], 'notifications'],
            'notifications_enabled' => [$data['notifications_enabled'], 'notifications'],
            'notify_low_stock' => [$data['notify_low_stock'], 'notifications'],
            'notify_warranty' => [$data['notify_warranty'], 'notifications'],
            'notify_maintenance' => [$data['notify_maintenance'], 'notifications'],
            'notify_license_expiry' => [$data['notify_license_expiry'], 'notifications'],
            'maintenance_generation_time' => [$data['maintenance_generation_time'], 'maintenance'],
            'notification_delivery_time' => [$data['notification_delivery_time'], 'notifications'],
            'smtp_mailer' => [$data['smtp_mailer'], 'email'],
            'smtp_host' => [$data['smtp_host'] ?? '', 'email'],
            'smtp_port' => [(int) ($data['smtp_port'] ?? 587), 'email'],
            'smtp_username' => [$data['smtp_username'] ?? '', 'email'],
            'smtp_encryption' => [$data['smtp_encryption'], 'email'],
            'mail_from_address' => [$data['mail_from_address'] ?? '', 'email'],
            'mail_from_name' => [$data['mail_from_name'], 'email'],
            'backup_schedule_time' => [$data['backup_schedule_time'], 'backup'],
            'automatic_backups' => [$data['automatic_backups'], 'backup'],
            'backup_retention_days' => [(int) $data['backup_retention_days'], 'backup'],
            'import_max_size_mb' => [(int) $data['import_max_size_mb'], 'import'],
            'import_max_rows' => [(int) $data['import_max_rows'], 'import'],
            'export_row_limit' => [(int) $data['export_row_limit'], 'export'],
            'default_export_format' => [$data['default_export_format'], 'export'],
            'inventory_units' => [$units->all(), 'inventory'],
            'items_per_page' => [(int) $data['items_per_page'], 'preferences'],
            'session_timeout_minutes' => [(int) $data['session_timeout_minutes'], 'preferences'],
            'approval_escalation_enabled' => [$data['approval_escalation_enabled'], 'approvals'],
        ];

        foreach (self::STATUS_LABELS as $status => $defaultLabel) {
            $settingsToSave['asset_status_label_'.$status] = [$data['asset_status_label_'.$status], 'assets'];
        }
        foreach (self::CONDITION_LABELS as $condition => $defaultLabel) {
            $settingsToSave['asset_condition_label_'.strtolower($condition)] = [$data['asset_condition_label_'.strtolower($condition)], 'assets'];
        }

        DB::transaction(function () use ($settingsToSave, $data) {
            foreach ($settingsToSave as $key => [$value, $group]) {
                SystemSetting::updateOrCreate(['key' => $key], [
                    'value' => ['value' => $value],
                    'group' => $group,
                ]);
            }

            if (filled($data['smtp_password'] ?? null)) {
                SystemSetting::updateOrCreate(['key' => 'smtp_password_encrypted'], [
                    'value' => ['value' => Crypt::encryptString($data['smtp_password'])],
                    'group' => 'email',
                ]);
            } elseif ($data['clear_smtp_password']) {
                SystemSetting::where('key', 'smtp_password_encrypted')->delete();
            }
        });

        if ($oldLogoPath && $newLogoPath !== $oldLogoPath) {
            Storage::disk('public')->delete($oldLogoPath);
        }

        Cache::forget('it-inventory.system-timezone');
        Cache::forget('it-inventory.system-config');
        Cache::forget('it-inventory.system-branding');
        Cache::forget('it-inventory.session-timeout');
        Cache::forget('it-inventory.mail-config');

        Audit::record('settings_updated', 'settings', SystemSetting::class, null, [], array_keys($settingsToSave));

        return back()->with('success', 'System settings saved. Runtime branding, regional, SMTP, import, export and scheduler options will use the updated configuration.');
    }

    public function testEmail(Request $request)
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);
        $data = $request->validate(['test_email' => 'required|email|max:190']);
        if (config('mail.default') !== 'smtp') {
            return back()->withErrors(['test_email' => 'Select SMTP server as the mail transport and save settings before testing outbound email.'])->withInput();
        }

        try {
            Mail::raw('This is a test message from '.config('app.name').'. Your configured outbound mail transport accepted the message.', function ($message) use ($data) {
                $message->to($data['test_email'])->subject(config('app.name').' SMTP test');
            });
        } catch (Throwable $exception) {
            report($exception);
            return back()->withErrors(['test_email' => 'The test email could not be sent. Check the SMTP host, port, encryption, credentials and outbound firewall rules.'])->withInput();
        }

        return back()->with('success', 'Test email handed to the configured mail transport. Check the recipient inbox and the mail server logs.');
    }
}
