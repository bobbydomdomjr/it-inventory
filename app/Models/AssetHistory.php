<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class AssetHistory extends Model
{
    public const UPDATED_AT = null;
    protected $table = 'asset_histories';
    protected $guarded = [];
    protected function casts(): array { return ['old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime']; }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Asset history is append-only.'));
        static::deleting(fn () => throw new LogicException('Asset history is append-only.'));
    }

    public function asset() { return $this->belongsTo(Asset::class); }
    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }
}
