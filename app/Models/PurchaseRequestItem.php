<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequestItem extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['est_unit_cost' => 'decimal:2']; }
    public function purchaseRequest() { return $this->belongsTo(PurchaseRequest::class); }
    public function category() { return $this->belongsTo(AssetCategory::class); }
}
