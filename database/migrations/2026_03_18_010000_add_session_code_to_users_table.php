<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'session_code')) {
                $table->string('session_code', 80)->nullable()->after('remember_token')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'session_code')) {
                $table->dropIndex(['session_code']);
                $table->dropColumn('session_code');
            }
        });
    }
};
