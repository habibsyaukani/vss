<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Tambahkan kolom ke alarm_raw
        if (Schema::hasTable('alarm_raw') && !Schema::hasColumn('alarm_raw', 'is_processed')) {
            Schema::table('alarm_raw', function (Blueprint $table) {
                $table->boolean('is_processed')->default(false)->after('raw_json');
                $table->index('is_processed', 'idx_alarm_raw_processed');
            });
            // Update data yang SUDAH ADA di idle_alarms agar tidak diproses ulang
            DB::statement("UPDATE alarm_raw INNER JOIN idle_alarms ON alarm_raw.guid = idle_alarms.guid SET alarm_raw.is_processed = 1");
        }

        // 2. Tambahkan kolom ke gps_tracks_raw
        if (Schema::hasTable('gps_tracks_raw') && !Schema::hasColumn('gps_tracks_raw', 'is_processed')) {
            Schema::table('gps_tracks_raw', function (Blueprint $table) {
                $table->boolean('is_processed')->default(false)->after('state_json');
                $table->index('is_processed', 'idx_gps_tracks_raw_processed');
            });
            // Update data yang SUDAH ADA di gps_tracks agar tidak diproses ulang
            DB::statement("UPDATE gps_tracks_raw INNER JOIN gps_tracks ON gps_tracks_raw.id = gps_tracks.raw_id SET gps_tracks_raw.is_processed = 1");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('alarm_raw') && Schema::hasColumn('alarm_raw', 'is_processed')) {
            Schema::table('alarm_raw', function (Blueprint $table) {
                $table->dropIndex('idx_alarm_raw_processed');
                $table->dropColumn('is_processed');
            });
        }

        if (Schema::hasTable('gps_tracks_raw') && Schema::hasColumn('gps_tracks_raw', 'is_processed')) {
            Schema::table('gps_tracks_raw', function (Blueprint $table) {
                $table->dropIndex('idx_gps_tracks_raw_processed');
                $table->dropColumn('is_processed');
            });
        }
    }
};
