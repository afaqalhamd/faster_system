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
            $table->string('password')->nullable()->after('email')->comment('Encrypted password for customer authentication');
            $table->string('remember_token')->nullable();
            $table->timestamp('email_verified_at')->nullable()->comment('Email verification timestamp');
            $table->text('fc_token')->nullable()->comment('Firebase Cloud Messaging Token for push notifications');
            $table->timestamp('last_login_at')->nullable()->comment('Last login timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropColumn([
                'password',
                'remember_token',
                'email_verified_at',
                'fc_token',
                'last_login_at'
            ]);
        });
    }
};
