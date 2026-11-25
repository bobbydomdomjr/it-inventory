<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['quantity_on_hand' => 'integer', 'reorder_level' => 'integer', 'min_level' => 'integer', 'unit_cost' => 'decimal:2']; }
    public function transactions() { return $this->hasMany(StockTransaction::class); }
    public function movements() { return $this->hasMany(StockMovement::class, 'stock_item_id'); }
    public function stockMovements() { return $this->hasMany(StockMovement::class, 'stock_item_id'); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function category() { return $this->belongsTo(AssetCategory::class); }
    public function location() { return $this->belongsTo(Location::class); }
    public function locationBalances() { return $this->hasMany(InventoryItemLocation::class, 'inventory_item_id'); }
    public function batches() { return $this->hasMany(InventoryBatch::class, 'inventory_item_id'); }
    public function reservations() { return $this->hasMany(StockReservation::class, 'inventory_item_id'); }
    public function transfers() { return $this->hasMany(StockTransfer::class, 'inventory_item_id'); }
}
