<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketMessage extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'is_staff_reply',
        'is_internal_note',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_staff_reply' => 'boolean',
        'is_internal_note' => 'boolean',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Relationships
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'message_id');
    }

    // Boot method to update ticket
    protected static function boot()
    {
        parent::boot();

        static::created(function ($message) {
            $message->ticket->increment('messages_count');
            $message->ticket->update(['last_reply_at' => now()]);

            if (!$message->is_staff_reply) {
                $message->ticket->increment('unread_messages_count');
            }
        });
    }
}
