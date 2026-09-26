<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_model_can_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'modelo@example.com',
            'password' => 'password123',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_verified_admin_is_sent_to_filament(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'password' => 'password123']);

        $response = $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_with_model_profile_prioritizes_filament(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'password' => 'password123']);

        $response = $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin');
    }

    public function test_verified_model_ignores_admin_intended_destination(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $response = $this->withSession(['url.intended' => '/admin'])
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password123',
            ]);

        $response->assertRedirect(route('account.dashboard'));
    }

    public function test_verified_model_ignores_other_internal_intended_destination(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $response = $this->withSession(['url.intended' => '/password/forgot'])
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password123',
            ]);

        $response->assertRedirect(route('account.dashboard'));
    }

    public function test_verified_user_without_profile_is_sent_to_incomplete_profile(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $user->modelProfile()->delete();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('account.incomplete-profile'));
    }

    public function test_unverified_model_cannot_log_in(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'modelo@example.com',
            'password' => 'password123',
        ]);

        $response = $this->from(route('login.show'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('login.show'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertFalse(Auth::check());
    }

    public function test_invalid_credentials_do_not_reveal_account_details(): void
    {
        User::factory()->create(['email' => 'modelo@example.com']);

        $response = $this->from(route('login.show'))->post(route('login.store'), [
            'email' => 'modelo@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login.show'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_validates_required_fields(): void
    {
        $response = $this->from(route('login.show'))->post(route('login.store'), []);

        $response->assertRedirect(route('login.show'));
        $response->assertSessionHasErrors(['email', 'password']);
    }
}
