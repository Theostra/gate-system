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
        Schema::table('gate_requests', function (Blueprint $table) {
            $table->string('ai_validation_status')->default('PENDING')->after('barcode');
            $table->boolean('ai_is_valid')->nullable()->after('ai_validation_status');
            $table->string('ai_extracted_vehicle')->nullable()->after('ai_is_valid');
            $table->string('ai_extracted_driver')->nullable()->after('ai_extracted_vehicle');
            $table->text('ai_reason')->nullable()->after('ai_extracted_driver');
            $table->text('ai_validation_feedback')->nullable()->after('ai_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gate_requests', function (Blueprint $table) {
            $table->dropColumn([
                'ai_validation_status',
                'ai_is_valid',
                'ai_extracted_vehicle',
                'ai_extracted_driver',
                'ai_reason',
                'ai_validation_feedback'
            ]);
        });
    }
};
