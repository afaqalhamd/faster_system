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
        Schema::create('ticket_status_history', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Reference
            $table->unsignedBigInteger('ticket_id')->comment('معرف التذكرة');

            // Status change
            $table->enum('old_status', ['new', 'open', 'pending', 'resolved', 'closed'])->nullable();
            $table->enum('new_status', ['new', 'open', 'pending', 'resolved', 'closed']);

            // Who made the change
            $table->unsignedBigInteger('changed_by')->comment('معرف المستخدم');

            // Notes
            $table->text('notes')->nullable()->comment('ملاحظات عن التغيير');

            // Timestamp
            $table->timestamp('created_at')->useCurrent();

            // Foreign keys
            $table->foreign('ticket_id')->references('id')->on('support_tickets')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('ticket_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_status_history');
    }
};
