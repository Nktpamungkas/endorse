<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('endorsements', function (Blueprint $table) {
            if (! Schema::hasColumn('endorsements', 'deleted_reason')) {
                $table->text('deleted_reason')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('endorsements', 'deleted_by')) {
                $table->foreignId('deleted_by')->nullable()->after('deleted_reason');
                $table->foreign('deleted_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('no action')
                    ->onUpdate('no action');
            }
            if (! Schema::hasColumn('endorsements', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('endorsements', function (Blueprint $table) {
            if (Schema::hasColumn('endorsements', 'deleted_by')) {
                $table->dropForeign(['deleted_by']);
                $table->dropColumn('deleted_by');
            }
            if (Schema::hasColumn('endorsements', 'deleted_reason')) {
                $table->dropColumn('deleted_reason');
            }
            if (Schema::hasColumn('endorsements', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
