<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NumberSequence extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['current_value' => 'integer']; }
}
