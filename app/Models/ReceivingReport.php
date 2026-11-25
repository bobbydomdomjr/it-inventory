<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReceivingReport extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['received_at' => 'date', 'accepted_at' => 'date']; }
    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function inspector() { return $this->belongsTo(User::class, 'inspected_by'); }
    public function location() { return $this->belongsTo(Location::class); }
    public function items() { return $this->hasMany(ReceivingReportItem::class); }
}
