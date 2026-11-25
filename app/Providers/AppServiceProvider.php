<?php

namespace App\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register application bindings here when needed.
    }

    public function boot(): void
    {
        $settings = [];

        try {
            $settings = Cache::remember('it-inventory.system-config', 300, function (): array {
                if (! Schema::hasTable('system_settings')) {
                    return [];
                }

                return DB::table('system_settings')->get()->mapWithKeys(function ($record): array {
                    $decoded = is_string($record->value) ? json_decode($record->value, true) : null;
                    $value = is_array($decoded) && array_key_exists('value', $decoded)
                        ? $decoded['value']
                        : $decoded;

                    return [$record->key => $value];
                })->all();
            });
        } catch (Throwable) {
            // Initial installation may not have created the system_settings table yet.
            $settings = [];
        }

        $get = static fn (string $key, mixed $default = null): mixed => $settings[$key] ?? $default;
        $timezone = $get('timezone');
        if (is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true)) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }

        $systemName = (string) $get('system_name', config('app.name', 'IT Inventory Management System'));
        $primaryColor = is_string($get('brand_primary')) && preg_match('/^#[0-9A-Fa-f]{6}$/', $get('brand_primary')) ? $get('brand_primary') : '#315f9f';
        $accentColor = is_string($get('brand_accent')) && preg_match('/^#[0-9A-Fa-f]{6}$/', $get('brand_accent')) ? $get('brand_accent') : '#0caa6a';
        $currencyCode = (string) $get('currency', 'PHP');
        $currencySymbols = ['PHP' => '₱', 'USD' => '$', 'EUR' => '€', 'SGD' => 'S$'];
        config(['app.name' => $systemName, 'app.currency_code' => $currencyCode, 'app.currency_symbol' => $currencySymbols[$currencyCode] ?? '₱']);
        config([
            'app.date_format' => $get('date_format', 'D, d M Y'),
            'app.time_format' => $get('time_format', 'H:i'),
            'session.lifetime' => max(15, min(720, (int) $get('session_timeout_minutes', config('session.lifetime', 120)))),
            'app.maintenance_generation_time' => $get('maintenance_generation_time', '06:00'),
            'app.notification_delivery_time' => $get('notification_delivery_time', '08:00'),
            'app.backup_schedule_time' => $get('backup_schedule_time', '01:30'),
            'app.items_per_page' => in_array((int) $get('items_per_page', 15), [10, 15, 25, 50, 100], true) ? (int) $get('items_per_page', 15) : 15,
        ]);

        $mailMailer = $get('smtp_mailer');
        if (in_array($mailMailer, ['smtp', 'log'], true)) {
            config(['mail.default' => $mailMailer]);
        }
        if ($mailMailer === 'smtp') {
            $smtpPassword = null;
            $encryptedPassword = $get('smtp_password_encrypted');
            if (is_string($encryptedPassword) && $encryptedPassword !== '') {
                try {
                    $smtpPassword = Crypt::decryptString($encryptedPassword);
                } catch (Throwable) {
                    // A rotated APP_KEY invalidates stored encrypted SMTP credentials; fail closed.
                    $smtpPassword = null;
                }
            }

            $encryption = $get('smtp_encryption', 'tls');
            config([
                'mail.mailers.smtp.host' => $get('smtp_host', config('mail.mailers.smtp.host')),
                'mail.mailers.smtp.port' => (int) $get('smtp_port', config('mail.mailers.smtp.port', 587)),
                'mail.mailers.smtp.username' => $get('smtp_username', config('mail.mailers.smtp.username')),
                'mail.mailers.smtp.password' => $smtpPassword,
                'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
                'mail.mailers.smtp.auto_tls' => $encryption !== 'none',
                'mail.from.address' => $get('mail_from_address', config('mail.from.address')),
                'mail.from.name' => $get('mail_from_name', $systemName),
            ]);
        }

        $logoPath = $get('organization_logo_path');
        $logoPathIsValid = is_string($logoPath)
            && preg_match('#\Abranding/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:png|jpe?g|webp)\z#i', $logoPath) === 1;
        $logoAvailable = false;
        if ($logoPathIsValid) {
            try {
                $logoAvailable = Storage::disk('public')->exists($logoPath);
            } catch (Throwable) {
                $logoAvailable = false;
            }
        }

        View::share('systemBranding', [
            'name' => $systemName,
            'short_name' => (string) $get('system_short_name', 'IT Inventory'),
            'organization_name' => (string) $get('organization_name', $systemName),
            'logo_path' => $logoPath,
            'logo_url' => $logoAvailable
                ? url('/branding/logo').'?v='.substr(hash('sha256', $logoPath), 0, 12)
                : null,
            'primary' => $primaryColor,
            'accent' => $accentColor,
        ]);
    }
}
