<?php
namespace App\Models;


use App\Models\Concerns\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;

class SoftwareLicenseAssignment extends Model
{
    use BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['assigned_at' => 'date', 'returned_at' => 'date']; }
    public function license() { return $this->belongsTo(SoftwareLicense::class, 'software_license_id'); }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function asset() { return $this->belongsTo(Asset::class); }
}
