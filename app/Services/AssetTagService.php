<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AssetTagService
{
    public function next(?string $prefix = null, ?int $year = null): string
    {
        $prefix = strtoupper(trim($prefix ?: $this->configuredPrefix()));
        $prefix = preg_replace('/[^A-Z0-9]+/', '-', $prefix) ?: 'IT';
        $year ??= (int) now()->year;
        $key = "asset-tag:{$prefix}:{$year}";

        return DB::transaction(function () use ($key, $prefix, $year): string {
            $now = now();
            DB::table('number_sequences')->insertOrIgnore([
                'key' => $key,
                'current_value' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $sequence = DB::table('number_sequences')->where('key', $key)->lockForUpdate()->first();
            if (! $sequence) {
                throw new RuntimeException('Unable to initialize the asset tag sequence.');
            }

            $next = ((int) $sequence->current_value) + 1;
            DB::table('number_sequences')->where('id', $sequence->id)->update([
                'current_value' => $next,
                'updated_at' => $now,
            ]);

            return sprintf('%s-%04d-%05d', $prefix, $year, $next);
        }, 3);
    }

    private function configuredPrefix(): string
    {
        $setting = SystemSetting::query()->where('key', 'asset_tag_prefix')->first();
        $configured = is_array($setting?->value) ? ($setting->value['value'] ?? null) : null;

        return is_string($configured) && $configured !== '' ? $configured : 'IT';
    }
}
