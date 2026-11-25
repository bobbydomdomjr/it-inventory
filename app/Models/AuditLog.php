<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected function casts(): array { return ['old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime']; }
    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit log entries are append-only.'));
        static::deleting(fn () => throw new LogicException('Audit log entries are append-only.'));
    }
    public function user() { return $this->belongsTo(User::class); }
}
