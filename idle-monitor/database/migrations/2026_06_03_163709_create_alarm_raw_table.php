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
        Schema::create('alarm_raw', function (Blueprint $table) {
            $table->id();
            $table->string('guid', 100)->unique();
            $table->string('device_id', 100);
            $table->string('device_name', 255);
            $table->integer('alarm_type');
            $table->text('alarm_value')->nullable();
            $table->tinyInteger('alarm_state');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->string('start_gps', 255)->nullable();
            $table->string('end_gps', 255)->nullable();
            $table->decimal('start_speed', 10, 2)->nullable();
            $table->decimal('end_speed', 10, 2)->nullable();
            $table->dateTime('report_time');
            $table->integer('duration_seconds')->nullable();
            $table->text('start_detail')->nullable();
            $table->text('end_detail')->nullable();
            $table->json('raw_json')->nullable();
            $table->timestamps();
            
            $table->index('device_id');
            $table->index('start_time');
            $table->index('report_time');
            $table->index('alarm_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alarm_raw');
    }
};
