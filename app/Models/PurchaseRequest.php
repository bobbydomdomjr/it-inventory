<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequest extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['total_estimated' => 'decimal:2']; }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function department() { return $this->belongsTo(Department::class); }
    public function items() { return $this->hasMany(PurchaseRequestItem::class); }
    public function quotations() { return $this->hasMany(Quotation::class); }
    public function purchaseOrder() { return $this->hasOne(PurchaseOrder::class); }
    public function approvals() { return $this->morphMany(Approval::class, 'approvable')->orderBy('round')->orderBy('sequence'); }
    public function documents() { return $this->morphMany(Document::class, 'documentable'); }
}
