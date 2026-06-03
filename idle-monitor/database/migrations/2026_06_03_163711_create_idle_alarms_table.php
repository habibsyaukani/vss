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
        Schema::create('idle_alarms', function (Blueprint $table) {
            $table->id();
            $table->string('guid', 100)->unique();
            $table->string('serial_no', 100)->nullable();
            $table->string('device_id', 100);
            $table->string('device_name', 255);
            $table->string('alarm_type', 100);
            $table->string('alarm_status', 50);
            $table->dateTime('starting_time');
            $table->string('starting_location', 255)->nullable();
            $table->dateTime('ending_time')->nullable();
            $table->string('ending_location', 255)->nullable();
            $table->text('start_detail')->nullable();
            $table->text('end_detail')->nullable();
            $table->decimal('start_speed', 10, 2)->nullable();
            $table->decimal('end_speed', 10, 2)->nullable();
            $table->dateTime('report_time');
            $table->integer('duration_seconds')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->decimal('latitude_start', 12, 8)->nullable();
            $table->decimal('longitude_start', 12, 8)->nullable();
            $table->decimal('latitude_end', 12, 8)->nullable();
            $table->decimal('longitude_end', 12, 8)->nullable();
            $table->timestamps();
            
            $table->index('device_id');
            $table->index('starting_time');
            $table->index('report_time');
            $table->index('duration_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idle_alarms');
    }
};
