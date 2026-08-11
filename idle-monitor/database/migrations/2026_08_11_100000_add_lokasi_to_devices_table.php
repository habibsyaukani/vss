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
        Schema::table('devices', function (Blueprint $table) {
            $table->string('lokasi', 100)->nullable()->after('location');
        });

        // Copy existing text location to lokasi if it doesn't contain a comma (ignoring GPS coordinates)
        DB::statement("UPDATE devices SET lokasi = location WHERE location IS NOT NULL AND location NOT LIKE '%,%'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('lokasi');
        });
    }
};
