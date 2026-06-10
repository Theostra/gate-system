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
            $table->string('company_name')->nullable()->after('driver_name');
            $table->string('company_address')->nullable()->after('company_name');
            $table->string('phone_number')->nullable()->after('company_address');
            $table->string('purpose')->nullable()->after('phone_number');
            $table->json('items')->nullable()->after('purpose');
            $table->string('vehicle_photo_path')->nullable()->after('manifest_document_path');
            $table->string('item_photo_path')->nullable()->after('vehicle_photo_path');
            $table->text('ga_notes')->nullable()->after('status');
            $table->string('barcode')->unique()->nullable()->after('ga_notes');
        });

        Schema::table('gate_logs', function (Blueprint $table) {
            $table->string('security_photo_path')->nullable()->after('notes');
            $table->json('checked_items')->nullable()->after('security_photo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gate_requests', function (Blueprint $table) {
            $table->dropColumn(['company_name', 'company_address', 'phone_number', 'purpose', 'items', 'vehicle_photo_path', 'item_photo_path', 'ga_notes', 'barcode']);
        });

        Schema::table('gate_logs', function (Blueprint $table) {
            $table->dropColumn(['security_photo_path', 'checked_items']);
        });
    }
};
