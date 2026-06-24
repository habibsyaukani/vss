<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gps_tracks_raw', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 64)->index();
            $table->string('device_name', 128)->nullable();
            $table->string('guid', 64)->nullable()->unique();

            // GPS Position
            $table->decimal('longitude', 11, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->smallInteger('altitude')->nullable()->comment('Unit: meter');
            $table->unsignedSmallInteger('speed')->nullable()->comment('Unit: km/h');
            $table->unsignedSmallInteger('direction')->nullable()->comment('0-360 degrees');
            $table->unsignedTinyInteger('satellites')->nullable();
            $table->unsignedSmallInteger('precision')->nullable()->comment('Unit: meter');
            $table->tinyInteger('mode')->nullable()->comment('1=GPS, 2=BD, 3=GLONASS');

            // Device State
            $table->tinyInteger('acc_state')->nullable()->comment('1=ACC on, 0=ACC off');
            $table->unsignedTinyInteger('record_state')->nullable()->comment('Bit flags per channel');
            $table->unsignedTinyInteger('video_mask_state')->nullable();
            $table->unsignedTinyInteger('video_lost_state')->nullable();
            $table->unsignedSmallInteger('io_state')->nullable();
            $table->tinyInteger('urgency')->nullable()->comment('1=Yes, 0=No');
            $table->tinyInteger('over_speed')->nullable()->comment('1=Yes, 0=No');
            $table->tinyInteger('low_speed')->nullable()->comment('1=Yes, 0=No');
            $table->float('oil_volume')->nullable()->comment('Fuel amount');

            // Network & Voltage
            $table->tinyInteger('net_type')->nullable();
            $table->tinyInteger('signal_value')->nullable();
            $table->float('dev_voltage')->nullable();
            $table->float('bat_voltage')->nullable();

            // Driver
            $table->string('driver_card_id', 64)->nullable()->index();
            $table->string('driver_name', 128)->nullable();

            // Timestamps from VSS
            $table->timestamp('gps_time')->nullable()->comment('GPS time (createtime from VSS)');
            $table->timestamp('report_time')->nullable()->comment('Time reported to platform');

            // Raw JSON for extra fields
            $table->json('state_json')->nullable()->comment('Raw stateJson from VSS');
            $table->json('tempe_humidity')->nullable()->comment('Temperature & humidity array');
            $table->tinyInteger('is_later')->default(0)->comment('1=retransmitted data');

            $table->timestamps();

            $table->index(['device_id', 'gps_time']);
            $table->index('gps_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gps_tracks_raw');
    }
};
