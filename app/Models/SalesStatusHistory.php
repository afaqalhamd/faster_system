<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Sale\Sale;
use App\Models\User;

class SalesStatusHistory extends Model
{
    use HasFactory;

    /**
     * Boot method to automatically set changed_by field
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->changed_by && auth()->id()) {
                $model->changed_by = auth()->id();
            }
            if (!$model->changed_at) {
                $model->changed_at = now();
            }
        });

        static::updating(function ($model) {
            // Don't automatically update changed_by on updates
            // as this should only be set when the record is created
        });
    }

    protected $fillable = [
        'sale_id',
        'previous_status',
        'new_status',
        'notes',
        'proof_image',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    /**
     * Get the sale that this status history belongs to.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the user who made this status change.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
