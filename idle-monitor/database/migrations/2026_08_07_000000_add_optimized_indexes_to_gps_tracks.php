<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gps_tracks', function (Blueprint $table) {
            // Composite index for fast filtering by device and time
            $table->index(['device_id', 'gps_time'], 'idx_device_time');
            // Composite index for fast filtering by time and speed
            $table->index(['gps_time', 'speed'], 'idx_time_speed');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gps_tracks', function (Blueprint $table) {
            $table->dropIndex('idx_device_time');
            $table->dropIndex('idx_time_speed');
        });
    }
};
