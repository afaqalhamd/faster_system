<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class SaleOrderStatusHistory extends Model
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
        'sale_order_id',
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
     * Get the sale order that this status history belongs to.
     */
    public function saleOrder(): BelongsTo
    {
        return $this->belongsTo(SaleOrder::class, 'sale_order_id');
    }

    /**
     * Get the user who made this status change.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
