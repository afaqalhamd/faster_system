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
        Schema::table('sales', function (Blueprint $table) {
            // Add new fields to track post-delivery actions
            $table->string('post_delivery_action')->nullable()->after('inventory_deducted_at')
                  ->comment('Action taken after delivery (Cancelled, Returned)');
            $table->timestamp('post_delivery_action_at')->nullable()->after('post_delivery_action')
                  ->comment('Timestamp when post-delivery action was taken');

            // Add index for better query performance
            $table->index(['post_delivery_action', 'post_delivery_action_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Drop the added columns
            $table->dropIndex(['post_delivery_action', 'post_delivery_action_at']);
            $table->dropColumn(['post_delivery_action', 'post_delivery_action_at']);
        });
    }
};
