<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyModelEmail;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_latest_token_verifies_email(): void
    {
        Notification::fake();
        $this->post(route('register.store'), $this->payload());
        $notification = Notification::sent(User::first(), VerifyModelEmail::class)->first();

        $response = $this->get(route('verification.verify', ['token' => $notification->token]));

        $response->assertRedirect(route('login.show'));
        $this->assertNotNull(User::first()->fresh()->email_verified_at);
        $this->assertDatabaseCount('email_verification_tokens', 0);
    }

    public function test_resending_verification_invalidates_previous_token(): void
    {
        Notification::fake();
        $this->post(route('register.store'), $this->payload());
        $first = Notification::sent(User::first(), VerifyModelEmail::class)->first();

        $this->post(route('verification.resend'), ['email' => 'modelo@example.com']);
        $notifications = Notification::sent(User::first(), VerifyModelEmail::class);
        $latest = $notifications->last();

        $this->assertNotSame($first->token, $latest->token);
        $this->get(route('verification.verify', ['token' => $first->token]))->assertSessionHas('error');
        $this->get(route('verification.verify', ['token' => $latest->token]))->assertRedirect(route('login.show'));
    }

    public function test_expired_verification_token_is_rejected(): void
    {
        Notification::fake();
        $this->post(route('register.store'), $this->payload());
        $notification = Notification::sent(User::first(), VerifyModelEmail::class)->first();

        Carbon::setTestNow(now()->addHours(25));
        $response = $this->get(route('verification.verify', ['token' => $notification->token]));
        Carbon::setTestNow();

        $response->assertSessionHas('error');
        $this->assertNull(User::first()->fresh()->email_verified_at);
    }

    private function payload(): array
    {
        return [
            'email' => 'modelo@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'name' => 'Modelo Mendoza',
            'whatsapp' => '+54 9 261 555 1234',
            'location' => 'Mendoza',
            'terms_accepted' => true,
            'privacy_accepted' => true,
        ];
    }
}
