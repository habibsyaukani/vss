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
        Schema::create('healing_logs', function (Blueprint $table) {
            $table->id();
            $table->string('issue_type', 50)->index(); // api_token, scheduler, queue, etc
            $table->string('severity', 20); // critical, warning, info
            $table->text('problem_description');
            $table->string('healing_action', 100); // refresh_token, restart_scheduler, etc
            $table->enum('status', ['attempted', 'success', 'failed'])->default('attempted');
            $table->text('result_message')->nullable();
            $table->timestamp('detected_at');
            $table->timestamp('healed_at')->nullable();
            $table->integer('execution_time_ms')->nullable(); // Time taken to heal
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('healing_logs');
    }
};
