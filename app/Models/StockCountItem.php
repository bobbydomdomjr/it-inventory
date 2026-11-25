<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;
use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;

class StockCountItem extends Model
{
    use TracksCreators, BelongsToOffice;
    protected $guarded = [];
    public function stockCount() { return $this->belongsTo(StockCount::class, 'stock_count_id'); }
    public function count() { return $this->stockCount(); }
    public function item() { return $this->belongsTo(InventoryItem::class, 'stock_item_id'); }
    public function location() { return $this->belongsTo(Location::class); }
}
