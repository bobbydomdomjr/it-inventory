<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetTransfer extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['approved_at' => 'datetime', 'received_at' => 'datetime']; }
    public function asset() { return $this->belongsTo(Asset::class); }
    public function fromEmployee() { return $this->belongsTo(Employee::class, 'from_employee_id'); }
    public function toEmployee() { return $this->belongsTo(Employee::class, 'to_employee_id'); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function receiver() { return $this->belongsTo(User::class, 'received_by'); }
    public function toDepartment() { return $this->belongsTo(Department::class, 'to_department_id'); }
    public function toLocation() { return $this->belongsTo(Location::class, 'to_location_id'); }
}
