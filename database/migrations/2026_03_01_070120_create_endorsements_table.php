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
        Schema::create('endorsements', function (Blueprint $table) {
            $table->id();
            $table->string('brand_name');
            $table->string('campaign_name')->nullable();
            $table->string('platform', 30);
            $table->string('content_type', 30);
            $table->string('status', 40)->default('deal_masuk');
            $table->date('deal_date')->nullable();
            $table->date('product_ordered_at')->nullable();
            $table->date('product_received_at')->nullable();
            $table->date('draft_deadline')->nullable();
            $table->boolean('storyline_required')->default(false);
            $table->boolean('storyline_done')->default(false);
            $table->boolean('drive_uploaded')->default(false);
            $table->date('approved_at')->nullable();
            $table->date('posting_date')->nullable();
            $table->date('posted_at')->nullable();
            $table->date('insight_due_at')->nullable();
            $table->date('insight_sent_at')->nullable();
            $table->boolean('boostcode_required')->default(false);
            $table->unsignedSmallInteger('boostcode_duration_days')->nullable();
            $table->boolean('self_purchase')->default(false);
            $table->string('checkout_proof_path')->nullable();
            $table->string('financial_mode', 40)->default('reimburse_duluan');
            $table->decimal('fee_amount', 15, 2)->default(0);
            $table->decimal('reimburse_amount', 15, 2)->default(0);
            $table->decimal('product_cost', 15, 2)->default(0);
            $table->decimal('other_cost', 15, 2)->default(0);
            $table->string('payment_status', 20)->default('belum_bayar');
            $table->date('payment_due_date')->nullable();
            $table->date('payment_received_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endorsements');
    }
};
