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
        Schema::create('gps_hourly_stats', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 64);
            $table->string('device_name', 128)->nullable();
            $table->date('record_date');
            $table->tinyInteger('record_hour')->unsigned()->comment('0-23');
            
            $table->decimal('max_speed', 5, 1)->default(0);
            $table->decimal('sum_speed', 12, 1)->default(0);
            $table->integer('total_records')->unsigned()->default(0);
            $table->decimal('avg_speed', 5, 1)->virtualAs('CASE WHEN total_records > 0 THEN sum_speed / total_records ELSE 0 END');

            $table->timestamps();

            // Indexes for fast querying
            $table->unique(['device_id', 'record_date', 'record_hour'], 'gps_hourly_unique');
            $table->index(['record_date', 'record_hour']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gps_hourly_stats');
    }
};
