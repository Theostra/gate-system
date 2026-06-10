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
        Schema::create('gate_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gate_request_id')->constrained('gate_requests')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('action'); // CHECK_IN, CHECK_OUT, REJECTED
            $table->string('scanned_barcode')->nullable();
            $table->json('ai_validation_result')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gate_logs');
    }
};
