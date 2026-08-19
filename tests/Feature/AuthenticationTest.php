<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_available_without_authentication(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Se connecter');
    }

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_authenticated_user_is_redirected_to_dashboard_from_homepage(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect('/dashboard');
    }

    public function test_user_can_log_in_and_access_dashboard(): void
    {
        $this->seed();

        $user = User::factory()->create([
            'password' => 'secret-password',
        ]);
        $user->assignRole('gestionnaire');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')
            ->assertOk();
    }

    public function test_user_without_access_admin_permission_cannot_view_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertForbidden();
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')
            ->assertNotFound();
    }
}
