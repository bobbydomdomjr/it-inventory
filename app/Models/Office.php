<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'short_name', 'email', 'phone', 'address', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function users(): HasMany { return $this->hasMany(User::class); }
    public function departments(): HasMany { return $this->hasMany(Department::class); }
    public function locations(): HasMany { return $this->hasMany(Location::class); }
    public function assets(): HasMany { return $this->hasMany(Asset::class); }
    public function inventoryItems(): HasMany { return $this->hasMany(InventoryItem::class); }
}
