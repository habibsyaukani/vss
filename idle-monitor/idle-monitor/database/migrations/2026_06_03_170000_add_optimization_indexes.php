<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optimize database performance with additional indexes
     * 
     * Indexes strategy:
     * - Single column indexes for common filters (device_id, alarm_status)
     * - Composite indexes for common query patterns
     * - Covering indexes for frequently accessed fields
     */
    public function up(): void
    {
        // alarm_raw table optimizations
        Schema::table('alarm_raw', function (Blueprint $table) {
            // Index for common filter: status checking
            $table->index('alarm_state');
            
            // Composite index for: filter by device + time range
            $table->index(['device_id', 'start_time']);
            
            // Composite index for: pagination + filtering
            $table->index(['alarm_type', 'start_time']);
        });

        // idle_alarms table optimizations
        Schema::table('idle_alarms', function (Blueprint $table) {
            // Index for common filter: alarm_status (CLOSED, ALARM_END, etc)
            $table->index('alarm_status');
            
            // Composite index for: filter by device + status + time
            $table->index(['device_id', 'alarm_status', 'starting_time']);
            
            // Composite index for: range queries on duration
            $table->index(['duration_minutes', 'starting_time']);
            
            // Composite index for: API queries (dashboard statistics)
            $table->index(['alarm_status', 'end_speed', 'starting_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alarm_raw', function (Blueprint $table) {
            $table->dropIndex(['alarm_state']);
            $table->dropIndex(['device_id', 'start_time']);
            $table->dropIndex(['alarm_type', 'start_time']);
        });

        Schema::table('idle_alarms', function (Blueprint $table) {
            $table->dropIndex(['alarm_status']);
            $table->dropIndex(['device_id', 'alarm_status', 'starting_time']);
            $table->dropIndex(['duration_minutes', 'starting_time']);
            $table->dropIndex(['alarm_status', 'end_speed', 'starting_time']);
        });
    }
};
