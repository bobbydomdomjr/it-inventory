<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array
    {
        return [
            'purchase_date' => 'date', 'warranty_expiration' => 'date', 'disposed_at' => 'date',
            'purchase_cost' => 'decimal:2', 'salvage_value' => 'decimal:2', 'specs' => 'array', 'useful_life_months' => 'integer',
        ];
    }
    public function office() { return $this->belongsTo(Office::class); }
    public function category() { return $this->belongsTo(AssetCategory::class, 'category_id'); }
    public function department() { return $this->belongsTo(Department::class); }
    public function location() { return $this->belongsTo(Location::class); }
    public function currentEmployee() { return $this->belongsTo(Employee::class, 'current_employee_id'); }
    public function assignments() { return $this->hasMany(Assignment::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function disposalRequests() { return $this->hasMany(AssetDisposal::class); }
    public function documents() { return $this->morphMany(Document::class, 'documentable'); }
    public function maintenanceRecords() { return $this->hasMany(MaintenanceRecord::class); }
    public function workOrders() { return $this->hasMany(WorkOrder::class); }
    public function histories() { return $this->hasMany(AssetHistory::class)->latest('created_at'); }
    public function depreciationEntries() { return $this->hasMany(DepreciationEntry::class); }
    public function procurement() { return $this->belongsTo(Procurement::class); }
    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
}
