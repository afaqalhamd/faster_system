<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $fillable = [
        'ticket_number',
        'user_id', // Kept for backward compatibility
        'ticketable_id',
        'ticketable_type',
        'category',
        'priority',
        'status',
        'subject',
        'description',
        'assigned_to',
        'resolved_at',
        'closed_at',
        'last_reply_at',
        'messages_count',
        'unread_messages_count',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'last_reply_at' => 'datetime',
        'messages_count' => 'integer',
        'unread_messages_count' => 'integer',
    ];

    protected $appends = ['status_label', 'priority_label', 'category_label'];

    // Relationships

    /**
     * Get the owning ticketable model (User or Party)
     */
    public function ticketable()
    {
        return $this->morphTo();
    }

    /**
     * Legacy relationship - kept for backward compatibility
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the creator of the ticket (User or Party)
     */
    public function getCreator()
    {
        return $this->ticketable ?? $this->user;
    }

    /**
     * Get creator's full name
     */
    public function getCreatorNameAttribute(): string
    {
        $creator = $this->getCreator();
        if (!$creator) return 'Unknown';

        return trim(($creator->first_name ?? '') . ' ' . ($creator->last_name ?? ''));
    }

    /**
     * Get creator's email
     */
    public function getCreatorEmailAttribute(): ?string
    {
        $creator = $this->getCreator();
        return $creator?->email;
    }

    /**
     * Check if ticket was created by a party (customer)
     */
    public function isCreatedByParty(): bool
    {
        return $this->ticketable_type === 'App\\Models\\Party\\Party';
    }

    /**
     * Check if ticket was created by a user (staff/admin)
     */
    public function isCreatedByUser(): bool
    {
        return $this->ticketable_type === 'App\\Models\\User';
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(TicketStatusHistory::class, 'ticket_id');
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'new' => 'جديد',
            'open' => 'مفتوح',
            'pending' => 'معلق',
            'resolved' => 'محلول',
            'closed' => 'مغلق',
            default => $this->status,
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'urgent' => 'عاجل',
            'high' => 'عالي',
            'medium' => 'متوسط',
            'low' => 'منخفض',
            default => $this->priority,
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'technical' => 'فني',
            'financial' => 'مالي',
            'delivery' => 'توصيل',
            'orders' => 'طلبات',
            'account' => 'حساب',
            'general' => 'عام',
            default => $this->category,
        };
    }

    // Scopes

    /**
     * Scope for tickets created by a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhere(function($q2) use ($userId) {
                  $q2->where('ticketable_type', 'App\\Models\\User')
                     ->where('ticketable_id', $userId);
              });
        });
    }

    /**
     * Scope for tickets created by a specific party
     */
    public function scopeForParty($query, $partyId)
    {
        return $query->where('ticketable_type', 'App\\Models\\Party\\Party')
                     ->where('ticketable_id', $partyId);
    }

    /**
     * Scope for tickets created by any ticketable (User or Party)
     */
    public function scopeForTicketable($query, $ticketableType, $ticketableId)
    {
        \Log::info("🔍 Searching for ticket - Type: $ticketableType - ID: $ticketableId");
        
        return $query->where('ticketable_type', $ticketableType)
                     ->where('ticketable_id', $ticketableId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    // Helper Methods
    public function canBeClosed(): bool
    {
        $canClose = in_array($this->status, ['open', 'pending', 'resolved']);
        \Log::info("🔍 canBeClosed check - Ticket: " . $this->id . " - Status: " . $this->status . " - Can close: " . ($canClose ? 'YES' : 'NO'));
        return $canClose;
    }

    public function canBeReopened(): bool
    {
        return $this->status === 'closed'
            && $this->closed_at
            && $this->closed_at->diffInDays(now()) <= 7;
    }

    public function markAsResolved(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    public function markAsClosed(): void
    {
        \Log::info("🔄 Marking ticket as closed: " . $this->id . " - Current status: " . $this->status);
        
        try {
            $result = $this->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);
            
            \Log::info("✅ Update result: " . ($result ? 'success' : 'failed'));
            \Log::info("✅ New status: " . $this->fresh()->status);
        } catch (\Exception $e) {
            \Log::error("❌ Error in markAsClosed: " . $e->getMessage());
            throw $e;
        }
    }

    public function reopen(): void
    {
        $this->update([
            'status' => 'open',
            'resolved_at' => null,
            'closed_at' => null,
        ]);
    }
}
