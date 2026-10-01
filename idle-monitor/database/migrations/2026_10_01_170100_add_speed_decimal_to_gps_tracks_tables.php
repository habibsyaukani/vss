<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE gps_tracks ADD COLUMN speed_decimal DECIMAL(10,2) NULL AFTER speed, ALGORITHM=INSTANT");
        DB::statement("ALTER TABLE gps_tracks_raw ADD COLUMN speed_decimal DECIMAL(10,2) NULL AFTER speed, ALGORITHM=INSTANT");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE gps_tracks DROP COLUMN speed_decimal, ALGORITHM=INSTANT");
        DB::statement("ALTER TABLE gps_tracks_raw DROP COLUMN speed_decimal, ALGORITHM=INSTANT");
    }
};
