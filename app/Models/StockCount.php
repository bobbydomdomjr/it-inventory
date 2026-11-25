<?php

namespace App\Models;


use App\Models\Concerns\BelongsToOffice;
use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockCount extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['counted_on' => 'date']; }
    public function items() { return $this->hasMany(StockCountItem::class); }
    public function location() { return $this->belongsTo(Location::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function counter() { return $this->belongsTo(User::class, 'counted_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
}
