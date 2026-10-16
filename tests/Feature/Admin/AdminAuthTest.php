<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_admin_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/admin/login');
    }

    public function test_admin_login_page_can_be_rendered(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('adminLoginForm');
    }

    public function test_admin_can_login_with_email(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/admin/login', [
            'username' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_can_login_with_name(): void
    {
        $user = User::factory()->create(['name' => 'Kepala Dapur']);

        $this->post('/admin/login', [
            'username' => 'Kepala Dapur',
            'password' => 'password',
        ])->assertRedirect('/admin/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->from('/admin/login')
            ->post('/admin/login', [
                'username' => $user->email,
                'password' => 'kata-sandi-salah',
            ])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_admin_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/admin/logout')
            ->assertRedirect('/admin/login');

        $this->assertGuest();
    }
}
