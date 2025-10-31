<?php

namespace App\Models\Party;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Party\PartyTransaction;
use App\Services\PartyService;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Party extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, InteractsWithMedia;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'party_type',
        'mobile',
        'phone',
        'whatsapp',
        'tax_number',
        'state_id',
        'shipping_address',
        'billing_address',
        'is_set_credit_limit',
        'credit_limit',
        'to_pay',
        'to_receive',
        'status',
        'is_wholesale_customer',
        'default_party',
        'currency_id',
        // Authentication fields
        'password',
        'fc_token',
        'last_login_at',
        'email_verified_at',
        // Audit fields
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'fc_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'status' => 'boolean',
        'is_set_credit_limit' => 'boolean',
        'is_wholesale_customer' => 'boolean',
        'default_party' => 'boolean',
    ];

    /**
     * Insert & update User Id's
     * */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Only set if not already set (allows manual override)
            if (!$model->created_by) {
                $model->created_by = auth()->id() ?? 1; // Default to system user (ID: 1)
            }
            if (!$model->updated_by) {
                $model->updated_by = auth()->id() ?? 1; // Default to system user (ID: 1)
            }
        });

        static::updating(function ($model) {
            // Only set if not already set (allows manual override)
            if (!$model->updated_by) {
                $model->updated_by = auth()->id() ?? 1; // Default to system user (ID: 1)
            }
        });
    }

    /**
     * Define the relationship between Party and User.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Define the relationship between Item Transaction & Items table.
     * Used to save Opening Balance and other payments
     * @return MorphMany
     */
    public function transaction(): MorphMany
    {
        return $this->morphMany(PartyTransaction::class, 'transaction');
    }

    public function getFullName()
    {
        return $this->first_name." ".$this->last_name;
    }

    public function getPartyTotalDueBalance()
    {
        $partyBalance = new PartyService();
        return $partyBalance->getPartyBalance($this->id);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Override getAuthPassword for Sanctum authentication
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Mutator to automatically hash password
     *
     * @param string $value
     * @return void
     */
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    /**
     * Accessor for full name
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Accessor for net balance
     *
     * @return float
     */
    public function getNetBalanceAttribute()
    {
        return $this->to_receive - $this->to_pay;
    }

    /**
     * Accessor for available credit
     *
     * @return float
     */
    public function getAvailableCreditAttribute()
    {
        return $this->credit_limit - $this->to_pay;
    }

    /**
     * Scope to get only active parties
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope to get only customers (exclude suppliers)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCustomersOnly($query)
    {
        return $query->whereIn('party_type', ['customer', 'both']);
    }

    /**
     * Define the relationship between Party and SaleOrders
     *
     * @return HasMany
     */
    public function saleOrders(): HasMany
    {
        return $this->hasMany(\App\Models\Sale\SaleOrder::class, 'party_id');
    }

    /**
     * Get all support tickets created by this party
     */
    public function supportTickets()
    {
        return $this->morphMany(\App\Models\SupportTicket::class, 'ticketable');
    }

    /**
     * Check if party has verified email
     *
     * @return bool
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Register media collections for profile image
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_image')
            ->singleFile() // Only one profile image at a time
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp']);
    }

    /**
     * Get profile image URL
     *
     * @return string|null
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('profile_image');
        return $media ? $media->getUrl() : null;
    }
}
