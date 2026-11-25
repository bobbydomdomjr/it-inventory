<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['started_at' => 'datetime', 'completed_at' => 'datetime', 'cost' => 'decimal:2', 'parts_used' => 'array']; }
    public function asset() { return $this->belongsTo(Asset::class); }
    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }
    public function maintenanceSchedule() { return $this->belongsTo(MaintenanceSchedule::class, 'maintenance_schedule_id'); }
    public function documents() { return $this->morphMany(Document::class, 'documentable'); }
}
