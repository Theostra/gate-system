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
            $table->enum('warehouse_type', [
                'RAW_MATERIAL', 
                'FINISHED_GOODS', 
                'PACKAGING', 
                'GENERAL', 
                'NON_WAREHOUSE',
                'OTHER'
            ])->default('GENERAL')->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gate_requests', function (Blueprint $table) {
            $table->dropColumn('warehouse_type');
        });
    }
};
