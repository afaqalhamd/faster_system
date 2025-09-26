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
        Schema::create('shipment_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipment_tracking_id');
            $table->string('document_type')->nullable(); // Invoice, Packing Slip, Delivery Receipt, etc.
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('shipment_tracking_id')->references('id')->on('shipment_trackings')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for better query performance
            $table->index(['shipment_tracking_id', 'document_type']);
            $table->index(['uploaded_by']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_documents');
    }
};
