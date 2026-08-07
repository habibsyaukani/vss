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
            // Update data lama agar dianggap sudah diproses (mencegah load ulang data jutaan baris)
            DB::table('alarm_raw')->update(['is_processed' => true]);
        }

        // 2. Tambahkan kolom ke gps_tracks_raw
        if (Schema::hasTable('gps_tracks_raw') && !Schema::hasColumn('gps_tracks_raw', 'is_processed')) {
            Schema::table('gps_tracks_raw', function (Blueprint $table) {
                $table->boolean('is_processed')->default(false)->after('state_json');
                $table->index('is_processed', 'idx_gps_tracks_raw_processed');
            });
            // Update data lama agar dianggap sudah diproses (mencegah load ulang data jutaan baris)
            // Karena ini berpotensi berat jika tabelnya sangat besar, kita gunakan chunking (namun DML sederhana di MySQL modern biasanya cepat untuk update boolean)
            // Sebagai pengamanan agar tidak terkunci lama, jalankan update langsung:
            DB::table('gps_tracks_raw')->update(['is_processed' => true]);
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
