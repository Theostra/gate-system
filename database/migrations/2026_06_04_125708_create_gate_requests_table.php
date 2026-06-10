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
        Schema::create('gate_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['INBOUND', 'OUTBOUND'])->default('INBOUND');
            $table->string('vehicle_number');
            $table->string('driver_name');
            $table->string('manifest_document_path')->nullable();
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'IN_LOCATION', 'COMPLETED', 'APPROVED_OUTBOUND', 'CHECKED_OUT'])->default('PENDING');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gate_requests');
    }
};
