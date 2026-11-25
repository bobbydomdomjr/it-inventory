<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoftwareLicense extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['license_key' => 'encrypted', 'expires_at' => 'date', 'purchase_cost' => 'decimal:2']; }
    public function assignments() { return $this->hasMany(SoftwareLicenseAssignment::class); }
}
