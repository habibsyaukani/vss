<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gps_tracks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('raw_id')->nullable()->index()->comment('FK to gps_tracks_raw.id');
            $table->string('device_id', 64)->index();
            $table->string('device_name', 128)->nullable();

            // Core display fields
            $table->decimal('longitude', 11, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->smallInteger('altitude')->nullable()->comment('Unit: meter');
            $table->unsignedSmallInteger('speed')->nullable()->comment('Unit: km/h');
            $table->unsignedSmallInteger('direction')->nullable()->comment('0-360 degrees');
            $table->unsignedTinyInteger('satellites')->nullable();
            $table->timestamp('gps_time')->nullable();
            $table->timestamp('report_time')->nullable();

            // Status flags
            $table->boolean('is_acc_on')->default(false);
            $table->boolean('is_overspeed')->default(false);
            $table->boolean('is_emergency')->default(false);
            $table->boolean('is_recording')->default(false)->comment('Derived from record_state');

            // Network & power
            $table->string('net_type_label', 16)->nullable()->comment('2G/3G/4G/5G/WiFi');
            $table->float('dev_voltage')->nullable();

            // Driver
            $table->string('driver_name', 128)->nullable();

            // Fleet
            $table->string('fleet_id', 64)->nullable();
            $table->string('fleet_name', 128)->nullable();

            // Mileage (calculated or from stateJson)
            $table->decimal('today_mileage', 10, 2)->nullable()->comment('km');
            $table->decimal('total_mileage', 12, 2)->nullable()->comment('km');

            // IO & Ignition
            $table->string('io_state_label', 64)->nullable()->comment('Human-readable IO status');
            $table->string('input_output_status', 64)->nullable()->comment('Formatted input/output display');

            $table->timestamps();

            $table->index(['device_id', 'gps_time']);
            $table->index('gps_time');
            $table->index('fleet_id');

            $table->foreign('raw_id')->references('id')->on('gps_tracks_raw')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gps_tracks');
    }
};
