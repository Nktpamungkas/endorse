<?php

namespace Tests\Feature;

use App\Models\Endorsement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndorsementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_endorsement_from_edit_route(): void
    {
        $user = $this->signIn();
        $endorsement = $this->createEndorsement($user);

        $response = $this->post("/endorsements/{$endorsement->id}", [
            '_method' => 'put',
            'brand_name' => 'Brand Update',
            'campaign_name' => 'Campaign Update',
            'platform' => 'instagram',
            'content_type' => 'reels',
            'status' => 'menunggu_posting',
            'deal_date' => '2026-03-27',
            'product_ordered_at' => null,
            'product_received_at' => null,
            'draft_deadline' => null,
            'storyline_required' => false,
            'storyline_done' => false,
            'drive_uploaded' => false,
            'approved_at' => null,
            'posting_date' => '2026-03-30',
            'posted_at' => null,
            'insight_due_at' => null,
            'insight_sent_at' => null,
            'boostcode_required' => false,
            'boostcode_duration_days' => null,
            'self_purchase' => false,
            'financial_mode' => 'na_dikirim_brand',
            'fee_amount' => 150000,
            'reimburse_amount' => 0,
            'product_cost' => 0,
            'other_cost' => 10000,
            'payment_status' => 'belum_bayar',
            'payment_due_date' => null,
            'payment_received_date' => null,
            'notes' => 'Catatan baru',
        ]);

        $response->assertRedirect("/endorsements/{$endorsement->id}");

        $endorsement->refresh();

        $this->assertSame('Brand Update', $endorsement->brand_name);
        $this->assertSame('menunggu_posting', $endorsement->status);
        $this->assertSame('instagram', $endorsement->platform);
        $this->assertSame('Catatan baru', $endorsement->notes);
    }

    public function test_edit_route_marks_payment_as_paid_when_status_is_completed(): void
    {
        $user = $this->signIn();
        $endorsement = $this->createEndorsement($user);

        $response = $this->post("/endorsements/{$endorsement->id}", [
            '_method' => 'put',
            'brand_name' => $endorsement->brand_name,
            'campaign_name' => $endorsement->campaign_name,
            'platform' => $endorsement->platform,
            'content_type' => $endorsement->content_type,
            'status' => 'selesai',
            'deal_date' => null,
            'product_ordered_at' => null,
            'product_received_at' => null,
            'draft_deadline' => null,
            'storyline_required' => false,
            'storyline_done' => false,
            'drive_uploaded' => false,
            'approved_at' => null,
            'posting_date' => null,
            'posted_at' => null,
            'insight_due_at' => null,
            'insight_sent_at' => null,
            'boostcode_required' => false,
            'boostcode_duration_days' => null,
            'self_purchase' => false,
            'financial_mode' => 'na_dikirim_brand',
            'fee_amount' => 150000,
            'reimburse_amount' => 0,
            'product_cost' => 0,
            'other_cost' => 10000,
            'payment_status' => 'belum_bayar',
            'payment_due_date' => null,
            'payment_received_date' => null,
            'notes' => 'Sudah selesai dan sudah dibayar',
        ]);

        $response->assertRedirect("/endorsements/{$endorsement->id}");

        $endorsement->refresh();

        $this->assertSame('selesai', $endorsement->status);
        $this->assertSame('lunas', $endorsement->payment_status);
        $this->assertNotNull($endorsement->payment_received_date);
    }

    public function test_user_can_update_endorsement_status_from_dashboard(): void
    {
        $user = $this->signIn();
        $endorsement = $this->createEndorsement($user);

        $response = $this->post("/endorsements/{$endorsement->id}/status", [
            'status' => 'menunggu_payment',
        ]);

        $response->assertRedirect();

        $endorsement->refresh();

        $this->assertSame('menunggu_payment', $endorsement->status);
    }

    public function test_completed_status_marks_payment_as_paid(): void
    {
        $user = $this->signIn();
        $endorsement = $this->createEndorsement($user);

        $response = $this->post("/endorsements/{$endorsement->id}/status", [
            'status' => 'selesai',
        ]);

        $response->assertRedirect();

        $endorsement->refresh();

        $this->assertSame('selesai', $endorsement->status);
        $this->assertSame('lunas', $endorsement->payment_status);
        $this->assertNotNull($endorsement->payment_received_date);
    }

    public function test_posting_date_completion_sets_insight_reminder_automatically(): void
    {
        $user = $this->signIn();
        $endorsement = $this->createEndorsement($user);

        $response = $this->post("/endorsements/{$endorsement->id}", $this->updatePayload($endorsement, [
            '_method' => 'put',
            'status' => 'deal_masuk',
            'posted_at' => '2026-04-01',
            'insight_due_at' => null,
        ]));

        $response->assertRedirect("/endorsements/{$endorsement->id}");

        $endorsement->refresh();

        $this->assertSame('menunggu_insight', $endorsement->status);
        $this->assertSame('2026-04-08', $endorsement->insight_due_at->format('Y-m-d'));
    }

    public function test_sent_insight_sets_waiting_payment_and_due_date_automatically(): void
    {
        $user = $this->signIn();
        $endorsement = $this->createEndorsement($user);

        $response = $this->post("/endorsements/{$endorsement->id}", $this->updatePayload($endorsement, [
            '_method' => 'put',
            'status' => 'menunggu_insight',
            'posted_at' => '2026-04-01',
            'insight_sent_at' => '2026-04-09',
            'payment_due_date' => null,
        ]));

        $response->assertRedirect("/endorsements/{$endorsement->id}");

        $endorsement->refresh();

        $this->assertSame('menunggu_payment', $endorsement->status);
        $this->assertSame('2026-04-23', $endorsement->payment_due_date->format('Y-m-d'));
    }

    public function test_user_can_update_fee_for_na_without_self_purchase(): void
    {
        $user = $this->signIn();
        $endorsement = $this->createEndorsement($user);

        $response = $this->post("/endorsements/{$endorsement->id}", [
            '_method' => 'put',
            'brand_name' => $endorsement->brand_name,
            'campaign_name' => $endorsement->campaign_name,
            'platform' => $endorsement->platform,
            'content_type' => $endorsement->content_type,
            'status' => $endorsement->status,
            'deal_date' => null,
            'product_ordered_at' => null,
            'product_received_at' => null,
            'draft_deadline' => null,
            'storyline_required' => false,
            'storyline_done' => false,
            'drive_uploaded' => false,
            'approved_at' => null,
            'posting_date' => null,
            'posted_at' => null,
            'insight_due_at' => null,
            'insight_sent_at' => null,
            'boostcode_required' => false,
            'boostcode_duration_days' => null,
            'self_purchase' => false,
            'financial_mode' => 'na_tanpa_produk',
            'fee_amount' => 490000,
            'reimburse_amount' => 0,
            'product_cost' => 0,
            'other_cost' => 0,
            'payment_status' => 'belum_bayar',
            'payment_due_date' => null,
            'payment_received_date' => null,
            'notes' => null,
        ]);

        $response->assertRedirect("/endorsements/{$endorsement->id}");

        $endorsement->refresh();

        $this->assertSame('na_tanpa_produk', $endorsement->financial_mode);
        $this->assertSame('490000.00', $endorsement->fee_amount);
        $this->assertSame('0.00', $endorsement->product_cost);
    }

    private function signIn(string $role = 'paid'): User
    {
        $user = User::factory()->create([
            'username' => fake()->unique()->userName(),
            'role' => $role,
            'active' => true,
            'session_code' => 'test-session-code',
            'trial_ends_at' => now()->addDay(),
        ]);

        $this->actingAs($user);
        $this->withSession(['user_session_code' => 'test-session-code']);

        return $user;
    }

    private function createEndorsement(User $user): Endorsement
    {
        return Endorsement::create([
            'user_id' => $user->id,
            'brand_name' => 'Brand Test',
            'campaign_name' => 'Campaign Test',
            'platform' => 'tiktok',
            'content_type' => 'video',
            'status' => 'deal_masuk',
            'financial_mode' => 'reimburse_duluan',
            'payment_status' => 'belum_bayar',
            'self_purchase' => true,
            'fee_amount' => 100000,
            'reimburse_amount' => 50000,
            'product_cost' => 25000,
            'other_cost' => 5000,
        ]);
    }

    private function updatePayload(Endorsement $endorsement, array $overrides = []): array
    {
        return array_merge([
            'brand_name' => $endorsement->brand_name,
            'campaign_name' => $endorsement->campaign_name,
            'platform' => $endorsement->platform,
            'content_type' => $endorsement->content_type,
            'status' => $endorsement->status,
            'deal_date' => null,
            'product_ordered_at' => null,
            'product_received_at' => null,
            'draft_deadline' => null,
            'storyline_required' => false,
            'storyline_done' => false,
            'drive_uploaded' => false,
            'approved_at' => null,
            'posting_date' => null,
            'posted_at' => null,
            'insight_due_at' => null,
            'insight_sent_at' => null,
            'boostcode_required' => false,
            'boostcode_duration_days' => null,
            'self_purchase' => true,
            'financial_mode' => 'reimburse_duluan',
            'fee_amount' => 100000,
            'reimburse_amount' => 50000,
            'product_cost' => 25000,
            'other_cost' => 5000,
            'payment_status' => 'belum_bayar',
            'payment_due_date' => null,
            'payment_received_date' => null,
            'notes' => null,
        ], $overrides);
    }
}
