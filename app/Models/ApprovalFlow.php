<?php

namespace App\Models;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalFlow extends Model
{
    use SoftDeletes, TracksCreators;
    protected $guarded = [];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function steps() { return $this->hasMany(ApprovalFlowStep::class)->orderBy('sequence'); }
}
