<?php

namespace Tests\Feature;

use App\Models\Endorsement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_received_net_profit_includes_completed_status(): void
    {
        $user = $this->signIn();

        Endorsement::create([
            'user_id' => $user->id,
            'brand_name' => 'Brand Selesai',
            'campaign_name' => 'Campaign Selesai',
            'platform' => 'tiktok',
            'content_type' => 'video',
            'status' => 'selesai',
            'financial_mode' => 'reimburse_duluan',
            'payment_status' => 'belum_bayar',
            'self_purchase' => true,
            'fee_amount' => 150000,
            'reimburse_amount' => 50000,
            'product_cost' => 70000,
            'other_cost' => 10000,
        ]);

        Endorsement::create([
            'user_id' => $user->id,
            'brand_name' => 'Brand Belum Selesai',
            'campaign_name' => 'Campaign Belum Selesai',
            'platform' => 'instagram',
            'content_type' => 'reels',
            'status' => 'menunggu_payment',
            'financial_mode' => 'na_tanpa_produk',
            'payment_status' => 'belum_bayar',
            'self_purchase' => false,
            'fee_amount' => 500000,
            'reimburse_amount' => 0,
            'product_cost' => 0,
            'other_cost' => 0,
        ]);

        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('receivedNetProfit', 120000)
                ->where('waitingPaymentItems.0.total_income', 500000)
            );
    }

    public function test_dashboard_detail_status_items_include_net_profit(): void
    {
        $user = $this->signIn();

        Endorsement::create([
            'user_id' => $user->id,
            'brand_name' => 'Brand Detail',
            'campaign_name' => 'Campaign Detail',
            'platform' => 'tiktok',
            'content_type' => 'video',
            'status' => 'deal_masuk',
            'financial_mode' => 'reimburse_duluan',
            'payment_status' => 'belum_bayar',
            'self_purchase' => true,
            'fee_amount' => 300000,
            'reimburse_amount' => 50000,
            'product_cost' => 100000,
            'other_cost' => 25000,
        ]);

        $this->get('/dashboard?status_view=deal_masuk')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('selectedStatusItems.0.brand_name', 'Brand Detail')
                ->where('selectedStatusItems.0.net_profit', 225000)
        );
    }

    public function test_dashboard_detail_status_items_follow_database_payment_status(): void
    {
        $user = $this->signIn();

        Endorsement::create([
            'user_id' => $user->id,
            'brand_name' => 'Brand Lunas',
            'campaign_name' => 'Campaign Lunas',
            'platform' => 'tiktok',
            'content_type' => 'video',
            'status' => 'selesai',
            'financial_mode' => 'reimburse_duluan',
            'payment_status' => 'belum_bayar',
            'self_purchase' => true,
            'fee_amount' => 200000,
            'reimburse_amount' => 50000,
            'product_cost' => 10000,
            'other_cost' => 5000,
        ]);

        $this->get('/dashboard?status_view=selesai')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('selectedStatusItems.0.payment_status', 'belum_bayar')
            );
    }

    private function signIn(): User
    {
        $user = User::factory()->create([
            'username' => fake()->unique()->userName(),
            'role' => 'paid',
            'active' => true,
            'session_code' => 'test-session-code',
            'trial_ends_at' => now()->addDay(),
        ]);

        $this->actingAs($user);
        $this->withSession(['user_session_code' => 'test-session-code']);

        return $user;
    }
}
