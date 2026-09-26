<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_request_has_generic_response_for_existing_and_unknown_emails(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'modelo@example.com']);

        $existing = $this->post(route('password.email'), ['email' => 'modelo@example.com']);
        $unknown = $this->post(route('password.email'), ['email' => 'unknown@example.com']);

        $existing->assertRedirect(route('password.request'))->assertSessionHas('status');
        $unknown->assertRedirect(route('password.request'))->assertSessionHas('status');
        Notification::assertSentTo(User::where('email', 'modelo@example.com')->first(), ResetPassword::class);
        Notification::assertNothingSentTo(User::where('email', 'unknown@example.com')->first() ?? new User);
    }

    public function test_password_can_be_reset_once_with_a_valid_token(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'modelo@example.com']);
        $this->post(route('password.email'), ['email' => $user->email]);
        $notification = Notification::sent($user, ResetPassword::class)->first();

        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertRedirect(route('login.show'))->assertSessionHas('status');
        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));

        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'another-password123',
            'password_confirmation' => 'another-password123',
        ])->assertRedirect(route('password.reset', [
            'token' => $notification->token,
            'email' => $user->email,
        ]));
    }

    public function test_expired_password_reset_token_is_rejected(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'modelo@example.com']);
        $this->post(route('password.email'), ['email' => $user->email]);
        $notification = Notification::sent($user, ResetPassword::class)->first();

        Carbon::setTestNow(now()->addMinutes(61));
        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);
        Carbon::setTestNow();

        $response->assertRedirect(route('password.reset', [
            'token' => $notification->token,
            'email' => $user->email,
        ]));
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_reset_requires_valid_input(): void
    {
        $response = $this->from(route('password.reset', ['token' => 'invalid', 'email' => 'modelo@example.com']))
            ->post(route('password.update'), []);

        $response->assertRedirect(route('password.reset', ['token' => 'invalid', 'email' => 'modelo@example.com']))
            ->assertSessionHasErrors(['email', 'password', 'token']);
    }
}
