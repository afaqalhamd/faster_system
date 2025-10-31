<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Party\Party;
use Carbon\Carbon;

class EmailVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'party_id',
        'email',
        'otp',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the party that owns the email verification.
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    /**
     * Check if the OTP is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the OTP is already verified.
     */
    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    /**
     * Mark the OTP as verified.
     */
    public function markAsVerified(): void
    {
        $this->update([
            'verified_at' => Carbon::now(),
        ]);
    }

    /**
     * Scope to get active (non-expired, non-verified) OTPs.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('verified_at')
                    ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Scope to get OTPs for a specific party and email.
     */
    public function scopeForPartyAndEmail($query, int $partyId, string $email)
    {
        return $query->where('party_id', $partyId)
                    ->where('email', $email);
    }
}
