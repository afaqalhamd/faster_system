<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Unique ticket number
            $table->string('ticket_number', 20)->unique()->comment('رقم التذكرة الفريد');

            // User information
            $table->unsignedBigInteger('user_id')->comment('معرف المستخدم');

            // Ticket classification
            $table->enum('category', [
                'technical',
                'financial',
                'delivery',
                'orders',
                'account',
                'general'
            ])->comment('تصنيف التذكرة');

            // Priority
            $table->enum('priority', [
                'urgent',
                'high',
                'medium',
                'low'
            ])->default('medium')->comment('مستوى الأولوية');

            // Status
            $table->enum('status', [
                'new',
                'open',
                'pending',
                'resolved',
                'closed'
            ])->default('new')->comment('حالة التذكرة');

            // Ticket content
            $table->string('subject')->comment('موضوع التذكرة');
            $table->text('description')->comment('وصف المشكلة');

            // Assignment
            $table->unsignedBigInteger('assigned_to')->nullable()->comment('معرف موظف الدعم المعين');

            // Dates
            $table->timestamp('resolved_at')->nullable()->comment('تاريخ الحل');
            $table->timestamp('closed_at')->nullable()->comment('تاريخ الإغلاق');
            $table->timestamp('last_reply_at')->nullable()->comment('تاريخ آخر رد');

            // Statistics
            $table->unsignedInteger('messages_count')->default(0)->comment('عدد الرسائل');
            $table->unsignedInteger('unread_messages_count')->default(0)->comment('عدد الرسائل غير المقروءة');

            // Timestamps
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('category');
            $table->index('priority');
            $table->index('created_at');
            $table->index('ticket_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
