<?php

namespace App\Services;

use App\Models\Asset;
use Carbon\Carbon;

class AssetValuationService
{
    /**
     * Calculate a non-posting straight-line depreciation estimate.
     * Existing posted ledger entries remain authoritative for the posted figures.
     */
    public function summarize(Asset $asset, ?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?: now())->copy()->startOfDay();
        $cost = round((float) $asset->purchase_cost, 2);
        $salvage = min($cost, max(0, round((float) $asset->salvage_value, 2)));
        $life = (int) ($asset->useful_life_months
            ?: $asset->category?->useful_life_months
            ?: $asset->category?->default_useful_life_months
            ?: 0);
        $method = $asset->depreciation_method ?: $asset->category?->depreciation_method ?: 'not_configured';

        $months = 0;
        $estimatedAccumulated = 0.0;
        $estimatedBookValue = $cost;
        $monthly = 0.0;

        if ($asset->purchase_date && $life > 0 && $method === 'straight_line') {
            $purchaseDate = Carbon::parse($asset->purchase_date)->startOfMonth();
            $months = max(0, $purchaseDate->diffInMonths($asOf->copy()->startOfMonth()));
            $months = min($months, $life);
            $depreciableBase = max(0, $cost - $salvage);
            $monthly = round($depreciableBase / $life, 2);
            $estimatedAccumulated = min($depreciableBase, round($monthly * $months, 2));
            // Correct rounding on the final month so book value never falls below salvage.
            if ($months >= $life) {
                $estimatedAccumulated = $depreciableBase;
            }
            $estimatedBookValue = max($salvage, round($cost - $estimatedAccumulated, 2));
        }

        $entries = $asset->relationLoaded('depreciationEntries')
            ? $asset->depreciationEntries
            : $asset->depreciationEntries()->whereDate('period', '<=', $asOf->toDateString())->get();
        $entries = $entries->filter(fn ($entry) => ! $entry->period || Carbon::parse($entry->period)->lte($asOf));
        $postedAccumulated = round((float) $entries->sum('amount'), 2);
        $latestPosted = $entries->sortBy('period')->last();

        return [
            'method' => $method,
            'useful_life_months' => $life,
            'months_elapsed' => $months,
            'monthly_depreciation' => $monthly,
            'estimated_accumulated' => $estimatedAccumulated,
            'estimated_book_value' => $estimatedBookValue,
            'posted_accumulated' => $postedAccumulated,
            'posted_book_value' => $latestPosted ? (float) $latestPosted->book_value : null,
            'posted_through' => $latestPosted?->period,
            'has_posted_entries' => $entries->isNotEmpty(),
        ];
    }
}
