<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetHistory;
use Illuminate\Support\Facades\Auth;

class AssetHistoryWriter
{
    public function record(Asset $asset, string $event, array $old = [], array $new = []): AssetHistory
    {
        return AssetHistory::create([
            'asset_id' => $asset->getKey(),
            'event' => $event,
            'old_values' => $this->sanitize($old),
            'new_values' => $this->sanitize($new),
            'actor_id' => Auth::id(),
            'created_at' => now(),
        ]);
    }

    private function sanitize(array $values): array
    {
        unset($values['password'], $values['license_key'], $values['_token'], $values['_method']);
        return $values;
    }
}
