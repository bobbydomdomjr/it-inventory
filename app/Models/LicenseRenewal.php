<?php

namespace App\Models;


use App\Models\Concerns\BelongsToOffice;
use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LicenseRenewal extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['renewed_on' => 'date', 'new_expiry' => 'date', 'cost' => 'decimal:2']; }
    public function license() { return $this->belongsTo(License::class); }
    public function renewedBy() { return $this->belongsTo(User::class, 'renewed_by'); }
}
