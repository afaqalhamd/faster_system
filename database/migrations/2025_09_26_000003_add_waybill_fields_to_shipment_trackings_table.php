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
        Schema::table('shipment_trackings', function (Blueprint $table) {
            // Waybill-specific fields
            $table->string('waybill_number')->nullable()->after('tracking_number');
            $table->string('waybill_type')->nullable()->after('waybill_number');
            $table->json('waybill_data')->nullable()->after('waybill_type');
            $table->boolean('waybill_validated')->default(false)->after('waybill_data');

            // Add index for waybill number for better query performance
            $table->index(['waybill_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_trackings', function (Blueprint $table) {
            $table->dropIndex(['waybill_number']);
            $table->dropColumn(['waybill_number', 'waybill_type', 'waybill_data', 'waybill_validated']);
        });
    }
};
