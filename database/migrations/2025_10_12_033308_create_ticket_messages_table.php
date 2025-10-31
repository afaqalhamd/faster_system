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
        Schema::create('ticket_messages', function (Blueprint $table) {
            // Primary key
            $table->id();

            // References
            $table->unsignedBigInteger('ticket_id')->comment('معرف التذكرة');
            $table->unsignedBigInteger('user_id')->comment('معرف المرسل');

            // Content
            $table->text('message')->comment('نص الرسالة');

            // Message type
            $table->boolean('is_staff_reply')->default(false)->comment('هل الرد من الدعم');
            $table->boolean('is_internal_note')->default(false)->comment('ملاحظة داخلية (للدعم فقط)');

            // Read status
            $table->boolean('is_read')->default(false)->comment('هل تم قراءة الرسالة');
            $table->timestamp('read_at')->nullable()->comment('تاريخ القراءة');

            // Timestamps
            $table->timestamps();

            // Foreign keys
            $table->foreign('ticket_id')->references('id')->on('support_tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('ticket_id');
            $table->index('user_id');
            $table->index('created_at');
            $table->index('is_read');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
    }
};
