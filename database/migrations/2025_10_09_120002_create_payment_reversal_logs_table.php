<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * إنشاء جدول لتسجيل عمليات الاسترداد والمراجعة
     */
    public function up(): void
    {
        Schema::create('payment_reversal_logs', function (Blueprint $table) {
            $table->id();

            // ربط بطلب البيع
            $table->unsignedBigInteger('sale_order_id');
            $table->foreign('sale_order_id')->references('id')->on('sale_orders');

            // ربط بالمعاملة المالية الأصلية
            $table->unsignedBigInteger('original_payment_id');
            $table->foreign('original_payment_id')->references('id')->on('payment_transactions');

            // ربط بمعاملة الاسترداد (إن وجدت)
            $table->unsignedBigInteger('reversal_payment_id')->nullable();
            $table->foreign('reversal_payment_id')->references('id')->on('payment_transactions');

            // نوع العملية
            $table->enum('action_type', [
                'automatic_reversal',
                'manual_reversal',
                'payment_flagged',
                'review_requested',
                'review_completed',
                'partial_refund',
                'store_credit_issued'
            ])->comment('Type of action performed');

            // الحالة السابقة والجديدة للطلب
            $table->string('previous_order_status')->nullable();
            $table->string('new_order_status')->nullable();

            // تفاصيل العملية
            $table->decimal('amount_involved', 20, 4)->default(0)
                  ->comment('Amount involved in this action');

            $table->text('action_reason')->nullable()
                  ->comment('Reason for the action');

            $table->json('action_details')->nullable()
                  ->comment('Additional details about the action');

            // معلومات المستخدم والتوقيت
            $table->unsignedBigInteger('performed_by');
            $table->foreign('performed_by')->references('id')->on('users');

            $table->timestamp('performed_at')->useCurrent();

            // حالة العملية
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])
                  ->default('pending');

            // رسالة الخطأ (إن وجدت)
            $table->text('error_message')->nullable();

            // معلومات إضافية للتتبع
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();

            // إضافة فهارس لتحسين الأداء
            $table->index(['sale_order_id', 'action_type']);
            $table->index(['performed_at', 'status']);
            $table->index(['action_type', 'status']);
            $table->index(['original_payment_id', 'action_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reversal_logs');
    }
};
