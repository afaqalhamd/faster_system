<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            // Drop old foreign key constraint
            $table->dropForeign(['user_id']);

            // Make user_id nullable (for backward compatibility)
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // Add polymorphic columns
            $table->unsignedBigInteger('ticketable_id')->nullable()->after('ticket_number');
            $table->string('ticketable_type')->nullable()->after('ticketable_id');

            // Add indexes for polymorphic relationship
            $table->index(['ticketable_id', 'ticketable_type']);
        });

        // Migrate existing data: copy user_id to polymorphic columns
        DB::statement("
            UPDATE support_tickets
            SET ticketable_id = user_id,
                ticketable_type = 'App\\\\Models\\\\User'
            WHERE user_id IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            // Remove polymorphic columns
            $table->dropIndex(['ticketable_id', 'ticketable_type']);
            $table->dropColumn(['ticketable_id', 'ticketable_type']);

            // Restore user_id as required
            $table->unsignedBigInteger('user_id')->nullable(false)->change();

            // Restore foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
