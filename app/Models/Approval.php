<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Approval extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['acted_at' => 'datetime', 'decided_at' => 'datetime', 'escalated_at' => 'datetime', 'round' => 'integer']; }
    public function approvable(): MorphTo { return $this->morphTo(); }
    public function approver() { return $this->belongsTo(User::class, 'approver_id'); }
    public function step() { return $this->belongsTo(ApprovalFlowStep::class, 'step_id'); }
}
