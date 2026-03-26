<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_root_shows_landing_page(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Endorse Tracker');
    }

    public function test_login_page_disables_browser_cache(): void
    {
        $response = $this->get(route('login.form'));

        $response
            ->assertOk()
            ->assertHeader('Pragma', 'no-cache');

        $cacheControl = (string) $response->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
    }

    public function test_single_user_can_login_and_access_dashboard(): void
    {
        $user = User::create([
            'name' => 'Master',
            'username' => 'dhedhepratiwi',
            'password' => Hash::make('dhedhepratiwi'),
            'role' => 'master',
            'active' => true,
        ]);

        $response = $this->post(route('login.attempt'), [
            'username' => 'dhedhepratiwi',
            'password' => 'dhedhepratiwi',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $dashboard = $this->get(route('dashboard'));
        $dashboard->assertOk();
    }
}
