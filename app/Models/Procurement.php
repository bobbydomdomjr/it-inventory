<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Procurement extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['requested_at' => 'date', 'expected_delivery_at' => 'date', 'received_at' => 'date', 'total_cost' => 'decimal:2', 'quantity' => 'integer', 'quantity_received' => 'integer', 'quantity_rejected' => 'integer']; }
    public function approvals() { return $this->morphMany(Approval::class, 'approvable')->orderBy('sequence'); }
    public function documents() { return $this->morphMany(Document::class, 'documentable'); }
    public function requesterUser() { return $this->belongsTo(User::class, 'requested_by_user_id'); }
    public function receiver() { return $this->belongsTo(User::class, 'received_by_user_id'); }
    public function assets() { return $this->hasMany(Asset::class); }
}
