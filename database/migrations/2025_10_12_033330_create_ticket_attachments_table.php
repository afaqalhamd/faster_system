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
        Schema::create('ticket_attachments', function (Blueprint $table) {
            // Primary key
            $table->id();

            // References
            $table->unsignedBigInteger('ticket_id')->comment('معرف التذكرة');
            $table->unsignedBigInteger('message_id')->nullable()->comment('معرف الرسالة (null للمرفقات الأولية)');

            // File information
            $table->string('file_name')->comment('اسم الملف الأصلي');
            $table->string('file_path', 500)->comment('مسار الملف');
            $table->string('file_type', 50)->comment('نوع الملف');
            $table->unsignedInteger('file_size')->comment('حجم الملف بالبايت');
            $table->string('mime_type', 100)->comment('MIME type');

            // Timestamp
            $table->timestamp('created_at')->useCurrent();

            // Foreign keys
            $table->foreign('ticket_id')->references('id')->on('support_tickets')->onDelete('cascade');
            $table->foreign('message_id')->references('id')->on('ticket_messages')->onDelete('cascade');

            // Indexes
            $table->index('ticket_id');
            $table->index('message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_attachments');
    }
};
