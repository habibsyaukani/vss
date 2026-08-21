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
        if (Schema::hasTable('alarm_raw')) {
            Schema::table('alarm_raw', function (Blueprint $table) {
                // Add index on created_at for fast cleanup query and statistics
                $table->index('created_at', 'alarm_raw_created_at_index');
            });
        }

        if (Schema::hasTable('gps_tracks_raw')) {
            Schema::table('gps_tracks_raw', function (Blueprint $table) {
                // Add index on created_at for fast cleanup query and statistics
                $table->index('created_at', 'gps_tracks_raw_created_at_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('alarm_raw')) {
            Schema::table('alarm_raw', function (Blueprint $table) {
                $table->dropIndex('alarm_raw_created_at_index');
            });
        }

        if (Schema::hasTable('gps_tracks_raw')) {
            Schema::table('gps_tracks_raw', function (Blueprint $table) {
                $table->dropIndex('gps_tracks_raw_created_at_index');
            });
        }
    }
};
