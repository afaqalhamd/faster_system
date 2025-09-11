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
        // Add carrier_id to sales table
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('carrier_id')->nullable()->after('state_id');
            $table->foreign('carrier_id')->references('id')->on('carriers');
        });

        // Add carrier_id to sale_orders table
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('carrier_id')->nullable()->after('state_id');
            $table->foreign('carrier_id')->references('id')->on('carriers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['carrier_id']);
            $table->dropColumn('carrier_id');
        });

        Schema::table('sale_orders', function (Blueprint $table) {
            $table->dropForeign(['carrier_id']);
            $table->dropColumn('carrier_id');
        });
    }
};
