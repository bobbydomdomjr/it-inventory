<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['hire_date' => 'date', 'transferred_at' => 'date', 'separated_at' => 'date']; }
    public function getFullNameAttribute(): string { return trim($this->first_name.' '.$this->last_name); }
    public function department() { return $this->belongsTo(Department::class); }
    public function user() { return $this->hasOne(User::class, 'employee_id'); }
    public function assets() { return $this->hasMany(Asset::class, 'current_employee_id'); }
    public function assignments() { return $this->hasMany(Assignment::class); }
}
