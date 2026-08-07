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
        // Cek apakah index sudah ada (karena user mungkin membuatnya manual lewat tinker)
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $indexesFound = $sm->listTableIndexes('gps_tracks');

        Schema::table('gps_tracks', function (Blueprint $table) use ($indexesFound) {
            // Composite index for fast filtering by device and time
            if (!array_key_exists('idx_device_time', $indexesFound)) {
                $table->index(['device_id', 'gps_time'], 'idx_device_time');
            }
            // Composite index for fast filtering by time and speed
            if (!array_key_exists('idx_time_speed', $indexesFound)) {
                $table->index(['gps_time', 'speed'], 'idx_time_speed');
            }
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
