<?php

namespace App\Models;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Software extends Model
{
    use SoftDeletes, TracksCreators;
    protected $table = 'software';
    protected $guarded = [];
    public function category() { return $this->belongsTo(AssetCategory::class, 'category_id'); }
    public function licenses() { return $this->hasMany(License::class); }
}
