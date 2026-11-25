<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceRecord extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['scheduled_at' => 'date', 'completed_at' => 'date', 'next_maintenance_at' => 'date', 'cost' => 'decimal:2', 'started_at' => 'datetime']; }
    public function asset() { return $this->belongsTo(Asset::class); }
    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }
    public function documents() { return $this->morphMany(Document::class, 'documentable'); }
}
