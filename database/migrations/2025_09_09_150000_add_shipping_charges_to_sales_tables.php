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
        // Add shipping charge columns to sales table
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('shipping_charge', 20, 4)->default(0)->after('note');
            $table->boolean('is_shipping_charge_distributed')->default(0)->after('shipping_charge');
        });

        // Add shipping charge columns to sale_orders table
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->decimal('shipping_charge', 20, 4)->default(0)->after('note');
            $table->boolean('is_shipping_charge_distributed')->default(0)->after('shipping_charge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['shipping_charge', 'is_shipping_charge_distributed']);
        });

        Schema::table('sale_orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_charge', 'is_shipping_charge_distributed']);
        });
    }
};
