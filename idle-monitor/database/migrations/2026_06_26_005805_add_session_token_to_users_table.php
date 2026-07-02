<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Token unik per sesi login (null = tidak ada yang login)
            $table->string('session_token', 100)->nullable()->after('remember_token');
            // Waktu login terakhir (untuk auto-logout 1 jam)
            $table->timestamp('login_at')->nullable()->after('session_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['session_token', 'login_at']);
        });
    }
};
