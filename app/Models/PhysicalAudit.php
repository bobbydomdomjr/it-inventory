<?php

namespace App\Models;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhysicalAudit extends Model
{
    protected $table = 'inventory_audits';
    use SoftDeletes, TracksCreators;
    protected $guarded = [];
    protected function casts(): array { return ['scheduled_at' => 'date', 'scheduled_for' => 'date', 'completed_at' => 'datetime', 'findings' => 'array']; }
    public function department() { return $this->belongsTo(Department::class); }
    public function location() { return $this->belongsTo(Location::class); }
    public function documents() { return $this->morphMany(Document::class, 'documentable'); }
    public function items() { return $this->hasMany(InventoryAuditItem::class, 'audit_id'); }
    public function lead() { return $this->belongsTo(User::class, 'lead_id'); }
}
