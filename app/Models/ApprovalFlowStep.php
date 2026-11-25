<?php

namespace App\Models;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalFlowStep extends Model
{
    use SoftDeletes, TracksCreators;
    protected $guarded = [];
    protected function casts(): array { return ['allow_return_for_revision' => 'boolean']; }
    public function flow() { return $this->belongsTo(ApprovalFlow::class, 'approval_flow_id'); }
    public function approverUser() { return $this->belongsTo(User::class, 'approver_user_id'); }
    public function approverRole() { return $this->belongsTo(Role::class, 'approver_role_id'); }
    public function approvals() { return $this->hasMany(Approval::class, 'step_id'); }
}
