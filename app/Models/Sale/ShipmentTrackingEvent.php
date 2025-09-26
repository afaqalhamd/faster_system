<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ShipmentTrackingEvent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shipment_tracking_id',
        'event_date',
        'location',
        'status',
        'description',
        'proof_image',
        'signature',
        'latitude',
        'longitude',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'event_date' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }

            // Set event date to current time if not provided
            if (!$model->event_date) {
                $model->event_date = now();
            }
        });
    }

    /**
     * Get the shipment tracking that owns this event.
     */
    public function shipmentTracking(): BelongsTo
    {
        return $this->belongsTo(ShipmentTracking::class);
    }

    /**
     * Get the user who created this event.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
