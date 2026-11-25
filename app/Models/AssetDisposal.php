<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetDisposal extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array
    {
        return [
            'requested_at' => 'date', 'disposed_at' => 'date', 'disposal_date' => 'date',
            'estimated_salvage_value' => 'decimal:2', 'disposal_value' => 'decimal:2',
            'salvage_value' => 'decimal:2', 'witnesses' => 'array',
        ];
    }
    public function asset() { return $this->belongsTo(Asset::class); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function documents() { return $this->morphMany(Document::class, 'documentable'); }
    public function approvals() { return $this->morphMany(Approval::class, 'approvable')->orderBy('sequence'); }
}
