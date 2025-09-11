<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Party\Party;
use App\Models\Items\ItemTransaction;
use App\Traits\FormatsDateInputs;
use App\Traits\FormatTime;
use App\Models\PaymentTransaction;
use App\Models\Sale\SaleOrder;
use App\Models\State;
use App\Models\Accounts\AccountTransaction;
use App\Models\Currency;
use App\Models\SalesStatusHistory;
use App\Models\Carrier;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;

    use FormatsDateInputs;

    use FormatTime;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sale_date',
        'sale_order_id',
        'quotation_id',
        'prefix_code',
        'count_id',
        'sale_code',
        'reference_no',
        'party_id',
        'state_id',
        'carrier_id',
        'note',
        'round_off',
        'grand_total',
        'paid_amount',
        'currency_id',
        'exchange_rate',
        'inventory_status',
        'inventory_deducted_at',
        'sales_status', // Add the new sales_status field
        'post_delivery_action',
        'post_delivery_action_at',
        'shipping_charge',
        'is_shipping_charge_distributed',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'inventory_deducted_at' => 'datetime',
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
     * This method calling the Trait FormatsDateInputs
     * @return null or string
     * Use it as formatted_sale_date
     * */
    public function getFormattedSaleDateAttribute()
    {
        return $this->toUserDateFormat($this->sale_date); // Call the trait method
    }

    /**
     * This method calling the Trait FormatTime
     * @return null or string
     * Use it as format_created_time
     * */
    public function getFormatCreatedTimeAttribute()
    {
        return $this->toUserTimeFormat($this->created_at); // Call the trait method
    }

    /**
     * Define the relationship between Order and User.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Define the relationship between Order and Party.
     *
     * @return BelongsTo
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'party_id');
    }

    /**
     * Define the relationship between Item Transaction & Sale Ordeer table.
     *
     * @return MorphMany
     */
    public function itemTransaction(): MorphMany
    {
        return $this->morphMany(ItemTransaction::class, 'transaction');
    }


    /**
     * Define the relationship between Expense Payment Transaction & Expense table.
     *
     * @return MorphMany
     */
    public function paymentTransaction(): MorphMany
    {
        return $this->morphMany(PaymentTransaction::class, 'transaction');
    }

    public function saleOrder() : BelongsTo
    {
        return $this->belongsTo(SaleOrder::class);
    }

    public function saleReturn() : HasMany
    {
        return $this->hasMany(SaleReturn::class, 'reference_no', 'sale_code');
    }

    public function state() : BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    /**
     * Define the relationship between Item Transaction & Items table.
     *
     * @return MorphMany
     */
    public function accountTransaction(): MorphMany
    {
        return $this->morphMany(AccountTransaction::class, 'transaction');
    }

    public function getTableCode()
    {
        return $this->sale_code;
    }

    public function quotation() : BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Get the sales status histories for the sale.
     */
    public function salesStatusHistories(): HasMany
    {
        return $this->hasMany(SalesStatusHistory::class, 'sale_id');
    }

    /**
     * Define the relationship between Sale and Carrier.
     *
     * @return BelongsTo
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'carrier_id');
    }

}
