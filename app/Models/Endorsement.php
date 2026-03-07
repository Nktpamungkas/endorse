<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Endorsement extends Model
{
    use HasFactory;

    public const STATUS_OPTIONS = [
        'deal_masuk' => 'Deal Masuk',
        'pembelian_produk' => 'Pembelian / Tunggu Produk',
        'pembuatan_draft' => 'Pembuatan Draft',
        'menunggu_draft_ok' => 'Menunggu Draft OK',
        'revisi' => 'Revisi',
        'menunggu_posting' => 'Menunggu Posting',
        'menunggu_insight' => 'Menunggu Insight',
        'menunggu_payment' => 'Menunggu Payment',
        'selesai' => 'Selesai',
    ];

    public const PLATFORM_OPTIONS = [
        'tiktok' => 'TikTok',
        'instagram' => 'Instagram',
        'tiktok_instagram' => 'TikTok + Instagram',
        'owning_content' => 'Owning Content (Konten Milik Brand)',
    ];

    public const CONTENT_TYPE_OPTIONS = [
        'video' => 'Video',
        'reels' => 'Reels',
        'story' => 'Story',
        'feed' => 'Feed',
        'live' => 'Live',
    ];

    public const FINANCIAL_MODE_OPTIONS = [
        'reimburse_duluan' => 'Reimburse Duluan',
        'reimburse_bersama_fee' => 'Reimburse Bareng Fee',
        'free_barter' => 'Free Endorse / Barter',
        'na_dikirim_brand' => 'N/A (Produk Dikirim Brand)',
        'na_tanpa_produk' => 'N/A (Tidak Ada Produk)',
    ];

    public const PAYMENT_STATUS_OPTIONS = [
        'belum_bayar' => 'Belum Bayar',
        'dp' => 'DP',
        'lunas' => 'Lunas',
    ];

    protected $fillable = [
        'user_id',
        'brand_name',
        'campaign_name',
        'platform',
        'content_type',
        'status',
        'deal_date',
        'product_ordered_at',
        'product_received_at',
        'draft_deadline',
        'storyline_required',
        'storyline_done',
        'drive_uploaded',
        'approved_at',
        'posting_date',
        'posted_at',
        'insight_due_at',
        'insight_sent_at',
        'boostcode_required',
        'boostcode_duration_days',
        'self_purchase',
        'checkout_proof_path',
        'financial_mode',
        'fee_amount',
        'reimburse_amount',
        'product_cost',
        'other_cost',
        'payment_status',
        'payment_due_date',
        'payment_received_date',
        'notes',
    ];

    protected $casts = [
        'deal_date' => 'date',
        'product_ordered_at' => 'date',
        'product_received_at' => 'date',
        'draft_deadline' => 'date',
        'storyline_required' => 'boolean',
        'storyline_done' => 'boolean',
        'drive_uploaded' => 'boolean',
        'approved_at' => 'date',
        'posting_date' => 'date',
        'posted_at' => 'date',
        'insight_due_at' => 'date',
        'insight_sent_at' => 'date',
        'boostcode_required' => 'boolean',
        'boostcode_duration_days' => 'integer',
        'self_purchase' => 'boolean',
        'fee_amount' => 'decimal:2',
        'reimburse_amount' => 'decimal:2',
        'product_cost' => 'decimal:2',
        'other_cost' => 'decimal:2',
        'payment_due_date' => 'date',
        'payment_received_date' => 'date',
    ];

    public function revisions(): HasMany
    {
        return $this->hasMany(EndorsementRevision::class)->orderByDesc('revision_date');
    }

    public function getTotalIncomeAttribute(): float
    {
        return (float) $this->fee_amount + (float) $this->reimburse_amount;
    }

    public function getTotalCostAttribute(): float
    {
        return (float) $this->product_cost + (float) $this->other_cost;
    }

    public function getNetProfitAttribute(): float
    {
        return $this->total_income - $this->total_cost;
    }

    public function getBoostcodeDeadlineAttribute(): ?Carbon
    {
        if (! $this->boostcode_required || ! $this->posted_at || ! $this->boostcode_duration_days) {
            return null;
        }

        return $this->posted_at->copy()->addDays((int) $this->boostcode_duration_days);
    }
}
