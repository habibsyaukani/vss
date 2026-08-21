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
        Schema::table('gps_tracks_raw', function (Blueprint $table) {
            // Add index on gps_time for faster date range queries
            $table->index('gps_time', 'idx_gps_tracks_raw_gps_time');
            
            // Add composite index for common queries
            $table->index(['device_id', 'gps_time'], 'idx_gps_tracks_raw_device_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gps_tracks_raw', function (Blueprint $table) {
            $table->dropIndex('idx_gps_tracks_raw_gps_time');
            $table->dropIndex('idx_gps_tracks_raw_device_time');
        });
    }
};
