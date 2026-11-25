<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierEvaluation extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['evaluated_on' => 'date', 'overall_score' => 'decimal:2'];
    }

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function evaluator() { return $this->belongsTo(User::class, 'evaluated_by'); }
}
