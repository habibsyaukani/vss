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
        Schema::table('gps_tracks', function (Blueprint $table) {
            // Covering index for SpeedPerformance & Dashboard queries
            $table->index(['gps_time', 'speed', 'device_id'], 'idx_gps_spd_dev');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gps_tracks', function (Blueprint $table) {
            $table->dropIndex('idx_gps_spd_dev');
        });
    }
};
