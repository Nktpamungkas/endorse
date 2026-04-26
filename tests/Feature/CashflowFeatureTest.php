<?php

namespace Tests\Feature;

use App\Models\Endorsement;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CashflowFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_pemasukan_and_pengeluaran(): void
    {
        $user = $this->signIn();

        $this->post('/pemasukan', [
            'tanggal' => '2026-04-20',
            'deskripsi' => 'Bonus live',
            'jumlah' => 250000,
        ])->assertRedirect('/pemasukan');

        $pemasukan = Pemasukan::firstOrFail();
        $this->assertSame('Bonus live', $pemasukan->deskripsi);

        $this->post('/pengeluaran', [
            'tanggal' => '2026-04-21',
            'deskripsi' => 'Beli tripod',
            'jumlah' => 100000,
        ])->assertRedirect('/pengeluaran');

        $pengeluaran = Pengeluaran::firstOrFail();
        $this->assertSame('Beli tripod', $pengeluaran->deskripsi);

        $this->put("/pemasukan/{$pemasukan->id}", [
            'tanggal' => '2026-04-22',
            'deskripsi' => 'Bonus live update',
            'jumlah' => 300000,
        ])->assertRedirect('/pemasukan');

        $this->delete("/pengeluaran/{$pengeluaran->id}")
            ->assertRedirect('/pengeluaran');

        $this->assertSame('Bonus live update', $pemasukan->fresh()->deskripsi);
        $this->assertDatabaseMissing('pengeluaran', ['id' => $pengeluaran->id]);
    }

    public function test_saldo_page_uses_paid_endorsements_plus_manual_cashflow(): void
    {
        $user = $this->signIn();

        Endorsement::create([
            'user_id' => $user->id,
            'brand_name' => 'Brand Lunas',
            'campaign_name' => 'Campaign A',
            'platform' => 'tiktok',
            'content_type' => 'video',
            'status' => 'selesai',
            'financial_mode' => 'reimburse_duluan',
            'payment_status' => 'lunas',
            'self_purchase' => true,
            'fee_amount' => 500000,
            'reimburse_amount' => 100000,
            'product_cost' => 10000,
            'other_cost' => 5000,
        ]);

        Endorsement::create([
            'user_id' => $user->id,
            'brand_name' => 'Brand Belum Bayar',
            'campaign_name' => 'Campaign B',
            'platform' => 'instagram',
            'content_type' => 'reels',
            'status' => 'menunggu_payment',
            'financial_mode' => 'na_tanpa_produk',
            'payment_status' => 'belum_bayar',
            'self_purchase' => false,
            'fee_amount' => 900000,
            'reimburse_amount' => 0,
            'product_cost' => 0,
            'other_cost' => 0,
        ]);

        Pemasukan::create([
            'user_id' => $user->id,
            'tanggal' => '2026-04-20',
            'deskripsi' => 'Bonus affiliate',
            'jumlah' => 200000,
        ]);

        Pengeluaran::create([
            'user_id' => $user->id,
            'tanggal' => '2026-04-21',
            'deskripsi' => 'Beli lampu',
            'jumlah' => 150000,
        ]);

        $this->get('/saldo')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Saldo')
                ->where('summary.total_diterima', 585000)
                ->where('summary.total_pemasukan', 200000)
                ->where('summary.total_pengeluaran', 150000)
                ->where('summary.saldo_akhir', 635000)
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
