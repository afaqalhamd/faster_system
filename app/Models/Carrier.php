<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Sale\Sale;
use App\Models\Sale\SaleOrder;

class Carrier extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'phone',
        'whatsapp',
        'address',
        'note',
        'status',
    ];

    /**
     * Insert & update User Id's
     * */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = auth()->id();
            $model->updated_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
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
     * Define the relationship between Carrier and Sales.
     *
     * @return HasMany
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'carrier_id');
    }

    /**
     * Define the relationship between Carrier and SaleOrders.
     *
     * @return HasMany
     */
    public function saleOrders(): HasMany
    {
        return $this->hasMany(SaleOrder::class, 'carrier_id');
    }

    /**
     * Define the relationship between Carrier and Users (carrier personnel).
     *
     * @return HasMany
     */
    public function carrierUsers(): HasMany
    {
        return $this->hasMany(User::class, 'carrier_id');
    }
}
