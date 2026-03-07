<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('endorsements', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('endorsement_revisions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('endorsement_id')->constrained()->nullOnDelete();
        });

        $masterId = DB::table('users')->where('username', 'dhedhepratiwi')->value('id');
        if ($masterId) {
            DB::table('endorsements')->whereNull('user_id')->update(['user_id' => $masterId]);
            DB::table('endorsement_revisions')->whereNull('user_id')->update(['user_id' => $masterId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('endorsement_revisions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('endorsements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
