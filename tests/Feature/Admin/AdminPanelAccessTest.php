<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_admin_can_access_the_admin_panel(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()->assertSee('Resumen');

        $this->actingAs($admin)
            ->get('/admin/model-profiles')
            ->assertOk();
    }

    public function test_guest_is_redirected_to_the_panel_login(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_unverified_admin_cannot_access_the_admin_panel(): void
    {
        $admin = User::factory()->unverified()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_regular_user_cannot_access_the_admin_panel(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }
}
