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
    public function test_root_redirects_to_login_when_not_authenticated(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login.form'));
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
