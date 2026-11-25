<?php
namespace App\Models;

use App\Models\Concerns\BelongsToOffice;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Document extends Model
{
    use BelongsToOffice;
    protected $guarded = [];
    public function documentable(): MorphTo { return $this->morphTo(); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
