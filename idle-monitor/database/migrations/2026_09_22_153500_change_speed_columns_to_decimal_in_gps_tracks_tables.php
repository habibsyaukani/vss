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
        // Change gps_tracks.speed
        Schema::table('gps_tracks', function (Blueprint $table) {
            // Drop existing integer column
            // We use change() if doctrine/dbal is installed, but sometimes it fails for unsignedSmallInteger
            // Let's use change() first:
            $table->decimal('speed', 10, 2)->nullable()->comment('Unit: km/h')->change();
        });

        // Change gps_tracks_raw.speed
        Schema::table('gps_tracks_raw', function (Blueprint $table) {
            $table->decimal('speed', 10, 2)->nullable()->comment('Unit: km/h')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gps_tracks', function (Blueprint $table) {
            $table->unsignedSmallInteger('speed')->nullable()->comment('Unit: km/h')->change();
        });

        Schema::table('gps_tracks_raw', function (Blueprint $table) {
            $table->unsignedSmallInteger('speed')->nullable()->comment('Unit: km/h')->change();
        });
    }
};
