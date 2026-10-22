<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyModelEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    public function test_registration_only_requests_credentials(): void
    {
        $this->withoutVite();
        $this->get(route('register.show'))->assertOk()
            ->assertSee('name="email"', false)->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertDontSee('name="name"', false)->assertDontSee('name="whatsapp"', false)
            ->assertDontSee('name="province_id"', false)->assertDontSee('name="publication_type_id"', false)
            ->assertDontSee('name="terms_accepted"', false);
    }

    public function test_registration_creates_only_an_unverified_account_and_sends_existing_notification(): void
    {
        Notification::fake();
        $this->post(route('register.store'), $this->payload() + [
            'name' => 'Ignored', 'is_admin' => true, 'email_verified_at' => now(),
        ])->assertRedirect(route('registration.pending'));
        $user = User::sole();
        $this->assertSame('profile_incomplete', $user->profile_completion_status);
        $this->assertNull($user->name);
        $this->assertNull($user->whatsapp);
        $this->assertNull($user->location);
        $this->assertNull($user->email_verified_at);
        $this->assertFalse($user->is_admin);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertDatabaseCount('model_profiles', 0);
        $this->assertDatabaseCount('policy_acceptances', 0);
        $this->assertGuest();
        Notification::assertSentTo($user, VerifyModelEmail::class);
    }

    public function test_registration_normalizes_email_and_rejects_duplicates(): void
    {
        Notification::fake();
        $this->post(route('register.store'), [...$this->payload(), 'email' => '  MODELO@example.com  '])
            ->assertRedirect(route('registration.pending'));
        $this->assertSame('modelo@example.com', User::sole()->email);
        $this->post(route('register.store'), $this->payload())->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_validates_credentials(): void
    {
        $this->post(route('register.store'), [])->assertSessionHasErrors(['email', 'password']);
        $this->post(route('register.store'), [...$this->payload(), 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors('password');
        $this->post(route('register.store'), [...$this->payload(), 'password_confirmation' => 'different'])
            ->assertSessionHasErrors('password');
        $this->post(route('register.store'), [...$this->payload(), 'email' => 'invalid'])
            ->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 0);
    }

    private function payload(): array
    {
        return ['email' => 'modelo@example.com', 'password' => 'password123', 'password_confirmation' => 'password123'];
    }
}
