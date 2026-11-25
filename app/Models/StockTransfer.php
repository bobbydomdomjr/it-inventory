<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use BelongsToOffice;

    protected $guarded = [];
    protected function casts(): array { return ['quantity' => 'integer', 'transferred_at' => 'datetime']; }

    public function item() { return $this->belongsTo(InventoryItem::class, 'inventory_item_id'); }
    public function fromLocation() { return $this->belongsTo(Location::class, 'from_location_id'); }
    public function toLocation() { return $this->belongsTo(Location::class, 'to_location_id'); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
}
