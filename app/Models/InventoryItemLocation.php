<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;

class InventoryItemLocation extends Model
{
    use BelongsToOffice;

    protected $guarded = [];
    protected function casts(): array { return ['quantity_on_hand' => 'integer']; }

    public function item() { return $this->belongsTo(InventoryItem::class, 'inventory_item_id'); }
    public function location() { return $this->belongsTo(Location::class); }
}
