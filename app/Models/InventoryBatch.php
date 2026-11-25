<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;

class InventoryBatch extends Model
{
    use BelongsToOffice;

    protected $guarded = [];
    protected function casts(): array { return ['expiry_date' => 'date', 'received_at' => 'date', 'quantity_on_hand' => 'integer', 'unit_cost' => 'decimal:2']; }

    public function item() { return $this->belongsTo(InventoryItem::class, 'inventory_item_id'); }
    public function location() { return $this->belongsTo(Location::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function movements() { return $this->hasMany(StockMovement::class, 'batch_id'); }
}
