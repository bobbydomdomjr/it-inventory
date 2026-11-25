<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    public function office() { return $this->belongsTo(Office::class); }
    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id'); }
    public function headEmployee() { return $this->belongsTo(Employee::class, 'head_employee_id'); }
    public function employees() { return $this->hasMany(Employee::class); }
    public function assets() { return $this->hasMany(Asset::class); }
}
