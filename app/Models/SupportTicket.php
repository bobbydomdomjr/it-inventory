<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;

use App\Models\Concerns\TracksCreators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model
{
    use SoftDeletes, TracksCreators, BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['first_response_at' => 'datetime', 'resolved_at' => 'datetime', 'sla_due_at' => 'datetime', 'escalated_at' => 'datetime', 'closed_at' => 'datetime']; }
    public function asset() { return $this->belongsTo(Asset::class); }
    public function requester() { return $this->belongsTo(User::class, 'requester_id'); }
    public function assignee() { return $this->belongsTo(User::class, 'assignee_id'); }
    public function comments() { return $this->hasMany(TicketComment::class)->latest(); }
    public function documents() { return $this->morphMany(Document::class, 'documentable'); }
}
