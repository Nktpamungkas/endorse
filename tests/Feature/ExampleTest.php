<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $response = $this->post(route('login.attempt'), [
            'username' => config('single_auth.username'),
            'password' => config('single_auth.password'),
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(session()->get(config('single_auth.session_key')));

        $dashboard = $this->get(route('dashboard'));
        $dashboard->assertOk();
    }
}
