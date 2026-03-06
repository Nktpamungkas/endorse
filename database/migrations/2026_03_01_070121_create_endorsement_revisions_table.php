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
        Schema::create('endorsement_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('endorsement_id')->constrained()->cascadeOnDelete();
            $table->date('revision_date');
            $table->text('note')->nullable();
            $table->boolean('uploaded_to_drive')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endorsement_revisions');
    }
};
