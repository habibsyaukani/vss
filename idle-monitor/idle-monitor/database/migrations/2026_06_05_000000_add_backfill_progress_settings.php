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
        // Seed initial system_settings if needed
        // These will be used to track backfill progress
        $existingKeys = \Illuminate\Support\Facades\DB::table('system_settings')
            ->whereIn('key', ['last_backfill_date', 'last_realtime_pull', 'backfill_completed_mei'])
            ->pluck('key')
            ->toArray();

        if (!in_array('last_backfill_date', $existingKeys)) {
            \Illuminate\Support\Facades\DB::table('system_settings')->insert([
                'key' => 'last_backfill_date',
                'value' => '2026-05-01',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!in_array('last_realtime_pull', $existingKeys)) {
            \Illuminate\Support\Facades\DB::table('system_settings')->insert([
                'key' => 'last_realtime_pull',
                'value' => now()->toDateTimeString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!in_array('backfill_completed_mei', $existingKeys)) {
            \Illuminate\Support\Facades\DB::table('system_settings')->insert([
                'key' => 'backfill_completed_mei',
                'value' => 'false',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Settings will remain for safety
        // Manual cleanup if needed
    }
};
