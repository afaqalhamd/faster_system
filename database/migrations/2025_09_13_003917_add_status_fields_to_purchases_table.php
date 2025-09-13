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
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('purchase_status')->default('Pending')->after('paid_amount');
            $table->string('inventory_status')->default('pending')->after('purchase_status');
            $table->timestamp('inventory_added_at')->nullable()->after('inventory_status');
            $table->string('post_receipt_action')->nullable()->after('inventory_added_at');
            $table->timestamp('post_receipt_action_at')->nullable()->after('post_receipt_action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn([
                'purchase_status',
                'inventory_status',
                'inventory_added_at',
                'post_receipt_action',
                'post_receipt_action_at'
            ]);
        });
    }
};
