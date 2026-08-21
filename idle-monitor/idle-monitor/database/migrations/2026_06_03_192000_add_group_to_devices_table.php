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
        Schema::table('devices', function (Blueprint $table) {
            // Add group_id if not exists
            if (!Schema::hasColumn('devices', 'group_id')) {
                $table->foreignId('group_id')
                    ->nullable()
                    ->constrained('device_groups')
                    ->onDelete('set null')
                    ->after('id');
            }
            
            // Add group_name if not exists (duplicate for query performance)
            if (!Schema::hasColumn('devices', 'group_name')) {
                $table->string('group_name', 100)->nullable()->after('device_name');
            }
            
            // Add status if not exists
            if (!Schema::hasColumn('devices', 'status')) {
                $table->enum('status', ['active', 'inactive'])->default('active')->after('sim_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn(['group_id', 'group_name', 'status']);
        });
    }
};
