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
        Schema::table('items', function (Blueprint $table) {
            $table->string('asin')->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('volume', 10, 2)->nullable();
            $table->string('image_url')->nullable();
            $table->string('cust_num')->nullable();
            $table->string('cust_num_t')->nullable();
            $table->decimal('cargo_fee', 10, 2)->nullable();
            $table->boolean('is_damaged')->default(false)->after('current_stock');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn([
                'asin',
                'weight',
                'volume',
                'image_url',
                'cust_num',
                'cust_num_t',
                'cargo_fee',
                'is_damaged',
            ]);
        });
    }
};
