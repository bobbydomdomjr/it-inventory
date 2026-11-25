<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReceivingReportItem extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['registered_ids' => 'array', 'expiry_date' => 'date']; }
    public function receivingReport() { return $this->belongsTo(ReceivingReport::class); }
    public function purchaseRequestItem() { return $this->belongsTo(PurchaseRequestItem::class); }
}
