<?php
namespace App\Models;


use App\Models\Concerns\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;

class TicketComment extends Model
{
    use BelongsToOffice;
    protected $guarded = [];
    protected function casts(): array { return ['is_internal' => 'boolean']; }
    public function ticket() { return $this->belongsTo(SupportTicket::class, 'support_ticket_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
