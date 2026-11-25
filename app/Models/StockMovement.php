<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class StockMovement extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected function casts(): array { return ['qty' => 'integer', 'unit_cost' => 'decimal:2', 'expiry_date' => 'date', 'created_at' => 'datetime']; }
    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Stock movement ledger rows are append-only.'));
        static::deleting(fn () => throw new LogicException('Stock movement ledger rows are append-only.'));
    }
    public function item() { return $this->belongsTo(InventoryItem::class, 'stock_item_id'); }
    public function performer() { return $this->belongsTo(User::class, 'performed_by'); }
    public function batch() { return $this->belongsTo(InventoryBatch::class, 'batch_id'); }
    public function location() { return $this->belongsTo(Location::class); }
    public function toLocation() { return $this->belongsTo(Location::class, 'to_location_id'); }
    public function reference(): MorphTo { return $this->morphTo(); }
}
