<?php

namespace App\Models;


use App\Models\Concerns\BelongsToOffice;
use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryAuditItem extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['scanned_at' => 'datetime']; }
    public function audit() { return $this->belongsTo(PhysicalAudit::class, 'audit_id'); }
    public function asset() { return $this->belongsTo(Asset::class); }
    public function seenLocation() { return $this->belongsTo(Location::class, 'seen_location_id'); }
    public function seenEmployee() { return $this->belongsTo(Employee::class, 'seen_employee_id'); }
    public function auditor() { return $this->belongsTo(User::class, 'auditor_id'); }
}
