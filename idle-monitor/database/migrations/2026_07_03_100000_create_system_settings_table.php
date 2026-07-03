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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('system_settings')->insert([
            [
                'key' => 'cleanup_enabled',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Enable/disable automatic cleanup of old raw data',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'cleanup_retention_days',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Number of days to keep raw data before cleanup',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'cleanup_last_run',
                'value' => null,
                'type' => 'string',
                'description' => 'Last time cleanup was executed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'cleanup_schedule',
                'value' => 'monthly',
                'type' => 'string',
                'description' => 'Cleanup schedule: daily, weekly, monthly',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
