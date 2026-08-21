<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('api_tokens', 'pid')) {
                $table->string('pid', 100)->nullable()->after('token');
            }
            if (!Schema::hasColumn('api_tokens', 'username')) {
                $table->string('username', 100)->nullable()->after('pid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('api_tokens', function (Blueprint $table) {
            $table->dropColumn(['pid', 'username']);
        });
    }
};
