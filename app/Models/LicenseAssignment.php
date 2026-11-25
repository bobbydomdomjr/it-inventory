<?php

namespace App\Models;


use App\Models\Concerns\BelongsToOffice;
use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LicenseAssignment extends Model
{
    use TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['assigned_at' => 'datetime', 'revoked_at' => 'datetime']; }
    public function license() { return $this->belongsTo(License::class); }
    public function assignable(): MorphTo { return $this->morphTo(); }
    public function assignedBy() { return $this->belongsTo(User::class, 'assigned_by'); }
}
