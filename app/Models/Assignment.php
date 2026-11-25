<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['issued_at' => 'date', 'due_date' => 'date', 'due_back_at' => 'datetime', 'returned_at' => 'date', 'ack_at' => 'datetime', 'acknowledged_at' => 'datetime', 'approved_at' => 'datetime', 'overdue_notified_at' => 'datetime']; }
    public function asset() { return $this->belongsTo(Asset::class); }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function issuer() { return $this->belongsTo(User::class, 'issued_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function department() { return $this->belongsTo(Department::class); }
    public function location() { return $this->belongsTo(Location::class); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
}
