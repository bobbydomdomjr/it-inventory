<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\DepreciationEntry;
use App\Models\SystemSetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AssetDepreciationService
{
    /**
     * Post an idempotent monthly straight-line depreciation schedule.
     * The configured start convention is either the acquisition month or the
     * month following acquisition. Other methods are deliberately skipped
     * until an approved accounting policy and calculation are configured.
     *
     * @return array{assets_processed:int,entries_created_or_updated:int,skipped_unconfigured:int,skipped_not_straight_line:int}
     */
    public function post(?CarbonImmutable $asOf = null, bool $dryRun = false): array
    {
        $cutoff = ($asOf ?? CarbonImmutable::now())->endOfMonth();
        $policy = $this->setting('depreciation_start_policy', 'next_month');

        if (! in_array($policy, ['next_month', 'purchase_month'], true)) {
            throw new InvalidArgumentException('The depreciation start policy is not supported.');
        }

        $summary = [
            'assets_processed' => 0,
            'entries_created_or_updated' => 0,
            'skipped_unconfigured' => 0,
            'skipped_not_straight_line' => 0,
        ];

        Asset::query()
            ->with('category')
            ->whereNotNull('purchase_date')
            ->where('purchase_cost', '>', 0)
            ->orderBy('id')
            ->chunkById(200, function ($assets) use ($cutoff, $policy, $dryRun, &$summary): void {
                foreach ($assets as $asset) {
                    $category = $asset->category;
                    $method = strtolower((string) ($asset->depreciation_method ?: $category?->depreciation_method ?: 'not_configured'));

                    if ($method !== 'straight_line') {
                        $summary['skipped_not_straight_line']++;
                        continue;
                    }

                    $lifeMonths = (int) ($asset->useful_life_months
                        ?: $category?->useful_life_months
                        ?: $category?->default_useful_life_months
                        ?: 0);

                    if ($lifeMonths < 1) {
                        $summary['skipped_unconfigured']++;
                        continue;
                    }

                    $cost = round((float) $asset->purchase_cost, 2);
                    $salvageValue = (float) $asset->salvage_value;
                    if ($salvageValue <= 0 && (float) ($category?->salvage_percent ?? 0) > 0) {
                        $salvageValue = $cost * ((float) $category->salvage_percent / 100);
                    }
                    $salvageValue = min($cost, max(0, round($salvageValue, 2)));
                    $depreciableAmount = max(0, round($cost - $salvageValue, 2));

                    $purchaseDate = CarbonImmutable::parse($asset->purchase_date);
                    $firstPeriod = $purchaseDate->startOfMonth();
                    if ($policy === 'next_month') {
                        $firstPeriod = $firstPeriod->addMonth();
                    }

                    $lastPeriod = $cutoff;
                    if ($asset->disposed_at) {
                        $disposalPeriod = CarbonImmutable::parse($asset->disposed_at)->endOfMonth();
                        if ($disposalPeriod->lessThan($lastPeriod)) {
                            $lastPeriod = $disposalPeriod;
                        }
                    }

                    if ($firstPeriod->greaterThan($lastPeriod)) {
                        continue;
                    }

                    $summary['assets_processed']++;
                    $monthlyBase = $lifeMonths > 0 ? $depreciableAmount / $lifeMonths : 0;
                    $accumulated = 0.0;

                    for ($period = $firstPeriod; $period->lessThanOrEqualTo($lastPeriod); $period = $period->addMonth()) {
                        $remaining = max(0, round($depreciableAmount - $accumulated, 2));
                        if ($remaining <= 0) {
                            break;
                        }

                        // Round each regular period to cents; the final period absorbs rounding residue.
                        $amount = min($remaining, round($monthlyBase, 2));
                        $monthsElapsed = $firstPeriod->diffInMonths($period);
                        if ($monthsElapsed + 1 >= $lifeMonths) {
                            $amount = $remaining;
                        }
                        $accumulated = min($depreciableAmount, round($accumulated + $amount, 2));
                        $bookValue = max($salvageValue, round($cost - $accumulated, 2));
                        $periodDate = $period->endOfMonth()->toDateString();

                        if (! $dryRun) {
                            DB::transaction(function () use ($asset, $periodDate, $amount, $bookValue): void {
                                DepreciationEntry::query()->updateOrCreate(
                                    ['asset_id' => $asset->id, 'period' => $periodDate],
                                    ['amount' => $amount, 'book_value' => $bookValue]
                                );
                            }, 3);
                        }

                        $summary['entries_created_or_updated']++;
                    }
                }
            });

        return $summary;
    }

    private function setting(string $key, mixed $default): mixed
    {
        $value = SystemSetting::query()->where('key', $key)->value('value');

        if (is_array($value)) {
            return $value['value'] ?? $default;
        }

        return $value ?? $default;
    }
}
