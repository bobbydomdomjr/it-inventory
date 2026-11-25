<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;

class StockReservation extends Model
{
    use BelongsToOffice;

    protected $guarded = [];
    protected function casts(): array { return ['quantity' => 'integer', 'fulfilled_qty' => 'integer', 'expires_at' => 'datetime', 'fulfilled_at' => 'datetime', 'released_at' => 'datetime']; }

    public function item() { return $this->belongsTo(InventoryItem::class, 'inventory_item_id'); }
    public function location() { return $this->belongsTo(Location::class); }
    public function reserver() { return $this->belongsTo(User::class, 'reserved_by'); }
    public function remainingQuantity(): int { return max(0, (int) $this->quantity - (int) $this->fulfilled_qty); }
}
