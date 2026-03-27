<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (Storage::disk('local')->exists('endorsement-files')) {
            Storage::disk('local')->deleteDirectory('endorsement-files');
        }

        Schema::dropIfExists('endorsement_files');
    }

    public function down(): void
    {
        Schema::create('endorsement_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('endorsement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 40)->default('local');
            $table->string('directory', 255);
            $table->string('stored_name', 255);
            $table->string('original_name', 255);
            $table->string('extension', 20)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->string('category', 20)->default('other');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('sha256_checksum', 64)->nullable();
            $table->timestamps();

            $table->index(['endorsement_id', 'created_at']);
            $table->index(['category', 'created_at']);
        });
    }
};
