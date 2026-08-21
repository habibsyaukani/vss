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
        Schema::create('device_groups', function (Blueprint $table) {
            $table->id();
            $table->string('group_code', 50)->unique(); // BUS, DT, FT, HD, PATROL, WT
            $table->string('group_name', 100);          // BUS - GPE, DT - GPE, etc
            $table->integer('total_devices')->default(0);
            $table->timestamps();
            
            // Indexes
            $table->index('group_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_groups');
    }
};
