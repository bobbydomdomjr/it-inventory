<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    public function assets() { return $this->hasMany(Asset::class); }
    public function inventoryItems() { return $this->hasMany(InventoryItem::class); }
    public function licenses() { return $this->hasMany(License::class); }
    public function procurements() { return $this->hasMany(Procurement::class); }
    public function quotations() { return $this->hasMany(Quotation::class); }
    public function evaluations() { return $this->hasMany(SupplierEvaluation::class)->latest('evaluated_on'); }
}
