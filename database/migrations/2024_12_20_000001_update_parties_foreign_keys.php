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
            // Drop existing foreign keys
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);

            // Re-add with onDelete clause
            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->foreign('updated_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            // Drop modified foreign keys
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);

            // Re-add original foreign keys
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }
};
