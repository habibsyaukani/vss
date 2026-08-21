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
        Schema::create('data_pull_batches', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 50)->index()->comment('Group batches dari 1 request');
            $table->integer('batch_number')->comment('Batch sequence number (1, 2, 3, ...)');
            $table->date('date')->index()->comment('Tanggal yang ditarik');
            $table->time('time_start')->comment('Waktu mulai batch (00:00:00)');
            $table->time('time_end')->comment('Waktu akhir batch (02:59:59)');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                  ->default('pending')
                  ->index()
                  ->comment('Status batch');
            $table->integer('total_records')->default(0)->comment('Jumlah records yang ditarik');
            $table->text('error_message')->nullable()->comment('Error message jika failed');
            $table->timestamp('started_at')->nullable()->comment('Waktu mulai proses');
            $table->timestamp('completed_at')->nullable()->comment('Waktu selesai proses');
            $table->timestamps();

            // Composite index untuk query per session
            $table->index(['session_id', 'batch_number'], 'idx_session_batch');
            $table->index(['session_id', 'status'], 'idx_session_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_pull_batches');
    }
};
