<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Ticket extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'ticket_type_id	',
        'user_id',
        'subject',
        'email',
        'description',
        'status',
        'awaiting_admin_reply',
    ];

    protected $casts = [
        'awaiting_admin_reply' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticketType()
    {
        return $this->belongsTo(TicketType::class, 'ticket_type_id');
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    /**
     * Check if customer can chat on this ticket.
     * Chat is disabled if awaiting admin reply (new tickets and reopened tickets).
     *
     * @return bool
     */
    public function canCustomerChat(): bool
    {
        return !$this->awaiting_admin_reply;
    }
}
