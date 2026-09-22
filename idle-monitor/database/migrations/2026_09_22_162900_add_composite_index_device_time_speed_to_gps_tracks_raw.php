<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add composite covering index for speed page queries.
     * Covers the common query pattern: WHERE device_id IN (...) AND gps_time >= ... AND speed > 0
     */
    public function up(): void
    {
        Schema::table('gps_tracks_raw', function (Blueprint $table) {
            $table->index(['device_id', 'gps_time', 'speed'], 'idx_gps_raw_device_time_speed');
        });
    }

    public function down(): void
    {
        Schema::table('gps_tracks_raw', function (Blueprint $table) {
            $table->dropIndex('idx_gps_raw_device_time_speed');
        });
    }
};
