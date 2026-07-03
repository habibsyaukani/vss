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
        // Cek apakah kolom sudah ada
        if (!Schema::hasColumn('system_settings', 'type')) {
            Schema::table('system_settings', function (Blueprint $table) {
                $table->string('type')->default('string')->after('value');
            });
        }

        if (!Schema::hasColumn('system_settings', 'description')) {
            Schema::table('system_settings', function (Blueprint $table) {
                $table->string('description')->nullable()->after('type');
            });
        }

        // Insert/Update default settings untuk cleanup
        $defaultSettings = [
            [
                'key' => 'cleanup_enabled',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Enable/disable automatic cleanup of old raw data',
            ],
            [
                'key' => 'cleanup_retention_days',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Number of days to keep raw data before cleanup',
            ],
            [
                'key' => 'cleanup_last_run',
                'value' => null,
                'type' => 'string',
                'description' => 'Last time cleanup was executed',
            ],
            [
                'key' => 'cleanup_schedule',
                'value' => 'monthly',
                'type' => 'string',
                'description' => 'Cleanup schedule: daily, weekly, monthly',
            ],
        ];

        foreach ($defaultSettings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting + ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('system_settings', 'description')) {
            Schema::table('system_settings', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }

        if (Schema::hasColumn('system_settings', 'type')) {
            Schema::table('system_settings', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
