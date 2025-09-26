<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Sale\SaleOrder;
use App\Models\Carrier;

class SimplifiedShipmentTracking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sale_order_id',
        'carrier_id',
        'tracking_number',
        'status',
        'estimated_delivery_date',
        'actual_delivery_date',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'estimated_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
    ];

    /**
     * Get the sale order that owns this tracking.
     */
    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class);
    }

    /**
     * Get the carrier for this tracking.
     */
    public function carrier()
    {
        return $this->belongsTo(Carrier::class);
    }
}
