<?php

namespace App\Models;


use App\Models\Concerns\BelongsToOffice;
use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceSchedule extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['next_due' => 'date', 'last_done' => 'date', 'is_active' => 'boolean']; }
    public function asset() { return $this->belongsTo(Asset::class); }
    public function category() { return $this->belongsTo(AssetCategory::class); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function workOrders() { return $this->hasMany(WorkOrder::class); }
}
