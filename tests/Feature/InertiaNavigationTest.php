<?php

namespace Tests\Feature;

use App\Models\Endorsement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InertiaNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_user_pages_return_inertia_components(): void
    {
        $user = $this->signIn();
        $endorsement = $this->createEndorsement($user);

        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));

        $this->get('/endorsements')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Endorsements/Index'));

        $this->get('/total-modal')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('TotalModal'));

        $this->get('/endorsements/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Endorsements/Create'));

        $this->get("/endorsements/{$endorsement->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Endorsements/Show'));

        $this->get("/endorsements/{$endorsement->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Endorsements/Edit'));

        $this->get('/profile/password')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Profile/Password'));
    }

    public function test_master_pages_return_inertia_components(): void
    {
        $user = $this->signIn('master');

        $this->get('/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Users/Index'));

        $deleted = $this->createEndorsement($user);
        $deleted->forceFill([
            'deleted_reason' => 'testing',
            'deleted_by' => $user->id,
        ])->save();
        $deleted->delete();

        $this->get('/endorsements-deleted')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Endorsements/Trashed'));

        $this->get("/endorsements-deleted/{$deleted->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Endorsements/Show'));
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
            'fee_amount' => 100000,
            'reimburse_amount' => 0,
            'product_cost' => 25000,
            'other_cost' => 5000,
        ]);
    }
}
