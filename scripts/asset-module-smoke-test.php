<?php

require dirname(__DIR__).'/vendor/autoload.php';

use App\Models\Asset;
use App\Models\DepreciationEntry;
use App\Services\AssetValuationService;
use Carbon\Carbon;

$asset = new Asset();
$asset->setRawAttributes([
    'purchase_cost' => 1200,
    'salvage_value' => 120,
    'purchase_date' => '2025-01-15',
    'useful_life_months' => 36,
    'depreciation_method' => 'straight_line',
]);
$asset->setRelation('category', null);
$asset->setRelation('depreciationEntries', collect());

$service = new AssetValuationService();
$valuation = $service->summarize($asset, Carbon::parse('2026-01-15'));

if ($valuation['estimated_accumulated'] !== 360.0 || $valuation['estimated_book_value'] !== 840.0) {
    throw new RuntimeException('Unexpected straight-line estimate: '.json_encode($valuation));
}

$posted = new DepreciationEntry();
$posted->setRawAttributes(['period' => '2025-12-31', 'amount' => 30, 'book_value' => 1170]);
$asset->setRelation('depreciationEntries', collect([$posted]));
$valuation = $service->summarize($asset, Carbon::parse('2026-01-15'));

if ($valuation['posted_accumulated'] !== 30.0 || $valuation['posted_book_value'] !== 1170.0) {
    throw new RuntimeException('Posted depreciation ledger summary did not match the supplied entries.');
}

echo "Asset valuation and posted-ledger smoke tests passed.\n";
