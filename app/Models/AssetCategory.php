<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetCategory extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $table = 'asset_categories';
    protected $guarded = [];
    protected function casts(): array { return ['is_active' => 'boolean', 'custom_fields' => 'array', 'salvage_percent' => 'decimal:3', 'useful_life_months' => 'integer', 'default_useful_life_months' => 'integer', 'default_min_stock_level' => 'integer']; }
    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id'); }
    public function assets() { return $this->hasMany(Asset::class, 'category_id'); }
    public function consumables() { return $this->hasMany(InventoryItem::class, 'category_id'); }
    public function software() { return $this->hasMany(Software::class, 'category_id'); }
}
