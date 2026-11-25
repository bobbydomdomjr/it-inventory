<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class License extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['license_key' => 'encrypted', 'purchase_date' => 'date', 'expiry_date' => 'date', 'cost' => 'decimal:2']; }
    public function software() { return $this->belongsTo(Software::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function assignments() { return $this->hasMany(LicenseAssignment::class); }
    public function renewals() { return $this->hasMany(LicenseRenewal::class); }
    public function getSeatsAssignedAttribute(): int { return $this->assignments()->whereNull('revoked_at')->count(); }
    public function getSeatsAvailableAttribute(): int { return max(0, (int) $this->seats_total - $this->seats_assigned); }
}
