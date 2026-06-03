<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Delete old TRUCK-001, TRUCK-002, TRUCK-003 mock data
     * Keep only real Howen device names (GPE-*, FT-*, etc)
     */
    public function up(): void
    {
        \DB::table('devices')->where('device_name', 'LIKE', '%TRUCK%')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reliably restore deleted data
        // This is a data cleanup, not reversible
    }
};
