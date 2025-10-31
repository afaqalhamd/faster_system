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
        Schema::table('parties', function (Blueprint $table) {
            // Add index on email for faster login queries
            $table->index('email');

            // Add index on mobile for faster searches
            $table->index('mobile');

            // Add composite index on party_type and status for filtering
            $table->index(['party_type', 'status']);

            // Add index on last_login_at for analytics
            $table->index('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['mobile']);
            $table->dropIndex(['party_type', 'status']);
            $table->dropIndex(['last_login_at']);
        });
    }
};
