<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Delete old TRUCK mock alarms
     */
    public function up(): void
    {
        \DB::table('idle_alarms')->where('device_name', 'LIKE', '%TRUCK%')->delete();
        \DB::table('alarm_raw')->where('device_name', 'LIKE', '%TRUCK%')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reliably restore deleted data
    }
};
