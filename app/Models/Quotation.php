<?php

namespace App\Models;


use App\Models\Concerns\BelongsToOffice;
use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['total' => 'decimal:2', 'is_selected' => 'boolean']; }
    public function purchaseRequest() { return $this->belongsTo(PurchaseRequest::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
}
