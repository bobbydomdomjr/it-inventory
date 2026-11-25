<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepreciationEntry extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['period' => 'date', 'amount' => 'decimal:2', 'book_value' => 'decimal:2']; }
    public function asset() { return $this->belongsTo(Asset::class); }
}
