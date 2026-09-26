<?php

namespace Tests\Feature\Auth;

use App\Models\PolicyAcceptance;
use App\Models\User;
use App\Notifications\VerifyModelEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_can_register_with_both_policy_acceptances(): void
    {
        Notification::fake();

        $response = $this->post(route('register.store'), $this->validPayload());

        $response->assertRedirect(route('registration.pending'));
        $this->assertDatabaseHas('users', [
            'email' => 'modelo@example.com',
            'whatsapp' => '+54 9 261 555 1234',
            'location' => 'Mendoza',
            'is_published' => false,
            'email_verified_at' => null,
        ]);
        $this->assertSame(2, PolicyAcceptance::query()->count());
        Notification::assertSentTo(User::where('email', 'modelo@example.com')->first(), VerifyModelEmail::class);
    }

    public function test_registration_requires_both_policy_acceptances(): void
    {
        $response = $this->from(route('register.show'))->post(route('register.store'), [
            ...$this->validPayload(),
            'privacy_accepted' => false,
        ]);

        $response->assertRedirect(route('register.show'));
        $response->assertSessionHasErrors('privacy_accepted');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'modelo@example.com']);

        $response = $this->from(route('register.show'))->post(route('register.store'), $this->validPayload());

        $response->assertRedirect(route('register.show'));
        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_requires_eight_character_password(): void
    {
        $response = $this->from(route('register.show'))->post(route('register.store'), [
            ...$this->validPayload(),
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertRedirect(route('register.show'));
        $response->assertSessionHasErrors('password');
    }

    public function test_registration_requires_all_model_fields(): void
    {
        $response = $this->from(route('register.show'))->post(route('register.store'), []);

        $response->assertRedirect(route('register.show'));
        $response->assertSessionHasErrors(['email', 'password', 'name', 'whatsapp', 'location']);
    }

    private function validPayload(): array
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
