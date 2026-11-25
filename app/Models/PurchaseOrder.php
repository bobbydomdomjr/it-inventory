<?php

namespace App\Models;


use App\Models\Concerns\BelongsToOffice;
use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['ordered_at' => 'date', 'total' => 'decimal:2']; }
    public function purchaseRequest() { return $this->belongsTo(PurchaseRequest::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function receivingReports() { return $this->hasMany(ReceivingReport::class); }
    public function assets() { return $this->hasMany(Asset::class); }
}
