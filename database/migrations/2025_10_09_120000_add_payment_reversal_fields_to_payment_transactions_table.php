<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * إضافة حقول لدعم نظام الاسترداد التلقائي للمدفوعات
     */
    public function up(): void
    {
        // التحقق من عدم وجود الحقول قبل إضافتها
        if (!Schema::hasColumn('payment_transactions', 'is_reversal')) {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->boolean('is_reversal')->default(false)->after('note')
                      ->comment('Indicates if this is a reversal/refund transaction');
            });
        }

        if (!Schema::hasColumn('payment_transactions', 'original_payment_id')) {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('original_payment_id')->nullable()->after('is_reversal')
                      ->comment('Reference to original payment transaction for reversals');
                $table->foreign('original_payment_id')->references('id')->on('payment_transactions');
            });
        }

        if (!Schema::hasColumn('payment_transactions', 'reversal_reason')) {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->string('reversal_reason')->nullable()->after('original_payment_id')
                      ->comment('Reason for payment reversal (Cancelled, Returned, etc.)');
            });
        }

        if (!Schema::hasColumn('payment_transactions', 'reversed_at')) {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->timestamp('reversed_at')->nullable()->after('reversal_reason')
                      ->comment('Timestamp when payment was reversed');
            });
        }

        if (!Schema::hasColumn('payment_transactions', 'reversed_by')) {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('reversed_by')->nullable()->after('reversed_at')
                      ->comment('User who processed the reversal');
                $table->foreign('reversed_by')->references('id')->on('users');
            });
        }

        if (!Schema::hasColumn('payment_transactions', 'payment_status')) {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->enum('payment_status', ['active', 'reversed', 'pending_reversal'])
                      ->default('active')->after('reversed_by')
                      ->comment('Status of the payment transaction');
            });
        }

        // إضافة الفهارس
        try {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->index(['is_reversal', 'payment_status'], 'pt_is_reversal_payment_status_idx');
            });
        } catch (\Exception $e) {
            // الفهرس موجود بالفعل
        }

        try {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->index(['original_payment_id', 'is_reversal'], 'pt_original_payment_id_is_reversal_idx');
            });
        } catch (\Exception $e) {
            // الفهرس موجود بالفعل
        }

        try {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->index(['transaction_type', 'transaction_id', 'is_reversal'], 'pt_transaction_is_reversal_idx');
            });
        } catch (\Exception $e) {
            // الفهرس موجود بالفعل
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            // حذف الفهارس أولاً
            try {
                $table->dropIndex('pt_is_reversal_payment_status_idx');
            } catch (\Exception $e) {}

            try {
                $table->dropIndex('pt_original_payment_id_is_reversal_idx');
            } catch (\Exception $e) {}

            try {
                $table->dropIndex('pt_transaction_is_reversal_idx');
            } catch (\Exception $e) {}

            // حذف المفاتيح الخارجية
            try {
                $table->dropForeign(['original_payment_id']);
            } catch (\Exception $e) {}

            try {
                $table->dropForeign(['reversed_by']);
            } catch (\Exception $e) {}

            // حذف الحقول
            if (Schema::hasColumn('payment_transactions', 'is_reversal')) {
                $table->dropColumn('is_reversal');
            }
            if (Schema::hasColumn('payment_transactions', 'original_payment_id')) {
                $table->dropColumn('original_payment_id');
            }
            if (Schema::hasColumn('payment_transactions', 'reversal_reason')) {
                $table->dropColumn('reversal_reason');
            }
            if (Schema::hasColumn('payment_transactions', 'reversed_at')) {
                $table->dropColumn('reversed_at');
            }
            if (Schema::hasColumn('payment_transactions', 'reversed_by')) {
                $table->dropColumn('reversed_by');
            }
            if (Schema::hasColumn('payment_transactions', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });
    }
};
