<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add alarm_state column to idle_alarms if not exists
        if (!Schema::hasColumn('idle_alarms', 'alarm_state')) {
            Schema::table('idle_alarms', function (Blueprint $table) {
                $table->tinyInteger('alarm_state')->default(0)->after('alarm_status')
                      ->comment('0=ALARM_END (idle selesai), 1=ALARMING (idle berlangsung)');
            });
        }
    }

    public function down(): void
    {
        Schema::table('idle_alarms', function (Blueprint $table) {
            $table->dropColumn('alarm_state');
        });
    }
};
