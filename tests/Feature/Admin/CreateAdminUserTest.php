<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_an_admin_with_a_hashed_password_and_panel_access(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Nombre', 'Administración')
            ->expectsQuestion('Email', 'ADMIN@example.com')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'A-long-secret-123')
            ->expectsQuestion('Repetí la contraseña', 'A-long-secret-123')
            ->assertSuccessful();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertTrue($admin->isVerifiedAdmin());
        $this->assertTrue(Hash::check('A-long-secret-123', $admin->password));
        $this->actingAs($admin)->get('/admin')->assertRedirect('/admin/model-profiles');
    }

    public function test_command_does_not_modify_an_existing_account(): void
    {
        $user = User::query()->create([
            'name' => 'Existing',
            'email' => 'existing@example.com',
            'password' => 'Existing-password-123',
        ]);

        $this->artisan('admin:create')
            ->expectsQuestion('Nombre', 'Administración')
            ->expectsQuestion('Email', $user->email)
            ->assertFailed();

        $this->assertFalse($user->fresh()->isAdmin());
        $this->assertDatabaseCount('users', 1);
    }

    public function test_command_rejects_mismatched_passwords(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Nombre', 'Administración')
            ->expectsQuestion('Email', 'admin@example.com')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'A-long-secret-123')
            ->expectsQuestion('Repetí la contraseña', 'A-different-secret-123')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }
}
