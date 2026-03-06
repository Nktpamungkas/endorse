<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('endorsements')
            ->where('status', 'sudah_posting')
            ->update(['status' => 'menunggu_insight']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op. We intentionally do not revert to avoid overwriting legitimate
        // "menunggu_insight" statuses created after this migration.
    }
};
