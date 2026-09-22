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
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE gps_tracks MODIFY speed DECIMAL(10,2) NULL COMMENT 'Unit: km/h'");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE gps_tracks_raw MODIFY speed DECIMAL(10,2) NULL COMMENT 'Unit: km/h'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE gps_tracks MODIFY speed SMALLINT UNSIGNED NULL COMMENT 'Unit: km/h'");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE gps_tracks_raw MODIFY speed SMALLINT UNSIGNED NULL COMMENT 'Unit: km/h'");
    }
};
