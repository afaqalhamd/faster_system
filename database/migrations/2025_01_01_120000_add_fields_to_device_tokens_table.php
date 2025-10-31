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
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->string('app_version')->nullable()->after('device_type');
            $table->json('device_info')->nullable()->after('app_version');
            $table->timestamp('last_used_at')->nullable()->after('device_info');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->dropColumn(['app_version', 'device_info', 'last_used_at']);
        });
    }
};
