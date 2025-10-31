<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Sale\SaleOrder;
use App\Models\PaymentTransaction;
use App\Models\User;

/**
 * نموذج سجل عمليات استرداد المدفوعات
 * Payment Reversal Log Model
 *
 * يستخدم لتسجيل جميع عمليات الاسترداد والمراجعة للمدفوعات
 * Used to log all payment reversal and review operations
 */
class PaymentReversalLog extends Model
{
    protected $table = 'payment_reversal_logs';

    protected $fillable = [
        'sale_order_id',
        'original_payment_id',
        'reversal_payment_id',
        'action_type',
        'previous_order_status',
        'new_order_status',
        'amount_involved',
        'action_reason',
        'action_details',
        'performed_by',
        'performed_at',
        'status',
        'error_message',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'action_details' => 'array',
        'performed_at' => 'datetime',
        'amount_involved' => 'decimal:4'
    ];

    /**
     * العلاقة مع طلب البيع
     * Relationship with sale order
     */
    public function saleOrder(): BelongsTo
    {
        return $this->belongsTo(SaleOrder::class, 'sale_order_id');
    }

    /**
     * العلاقة مع المعاملة المالية الأصلية
     * Relationship with original payment transaction
     */
    public function originalPayment(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'original_payment_id');
    }

    /**
     * العلاقة مع معاملة الاسترداد
     * Relationship with reversal payment transaction
     */
    public function reversalPayment(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'reversal_payment_id');
    }

    /**
     * العلاقة مع المستخدم الذي قام بالعملية
     * Relationship with user who performed the action
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Scope للحصول على السجلات الناجحة فقط
     * Scope to get only successful logs
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope للحصول على السجلات الفاشلة فقط
     * Scope to get only failed logs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope للحصول على السجلات حسب نوع العملية
     * Scope to get logs by action type
     */
    public function scopeByActionType($query, string $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Scope للحصول على السجلات لطلب بيع معين
     * Scope to get logs for specific sale order
     */
    public function scopeForSaleOrder($query, int $saleOrderId)
    {
        return $query->where('sale_order_id', $saleOrderId);
    }

    /**
     * Scope للحصول على السجلات لفترة زمنية معينة
     * Scope to get logs for specific date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('performed_at', [$startDate, $endDate]);
    }

    /**
     * Scope للحصول على سجلات اليوم
     * Scope to get today's logs
     */
    public function scopeToday($query)
    {
        return $query->whereDate('performed_at', today());
    }

    /**
     * احصل على اسم المستخدم الذي قام بالعملية
     * Get the name of user who performed the action
     */
    public function getPerformedByNameAttribute(): string
    {
        if ($this->performedBy) {
            return trim($this->performedBy->first_name . ' ' . $this->performedBy->last_name);
        }
        return 'Unknown';
    }

    /**
     * احصل على وصف نوع العملية
     * Get action type description
     */
    public function getActionTypeDescriptionAttribute(): string
    {
        $descriptions = [
            'automatic_reversal' => 'Automatic Payment Reversal',
            'manual_reversal' => 'Manual Payment Reversal',
            'payment_flagged' => 'Payment Flagged for Review',
            'review_requested' => 'Review Requested',
            'review_completed' => 'Review Completed',
            'partial_refund' => 'Partial Refund',
            'store_credit_issued' => 'Store Credit Issued'
        ];

        return $descriptions[$this->action_type] ?? $this->action_type;
    }

    /**
     * احصل على حالة العملية بشكل مقروء
     * Get readable status
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'Pending',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled'
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * احصل على لون الحالة للواجهة
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        $colors = [
            'pending' => 'warning',
            'completed' => 'success',
            'failed' => 'danger',
            'cancelled' => 'secondary'
        ];

        return $colors[$this->status] ?? 'info';
    }
}

