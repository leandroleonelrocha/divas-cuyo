<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginVerificationNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_shows_verification_card_for_the_verified_email_status(): void
    {
        $this->withSession(['status' => 'Tu correo fue verificado. Ya podés iniciar sesión.'])
            ->get(route('login.show'))
            ->assertOk()
            ->assertSee('¡Correo verificado!')
            ->assertSee('Tu cuenta está lista. Ya podés iniciar sesión')
            ->assertSee('role="status"', false)
            ->assertSee('aria-live="polite"', false)
            ->assertSee('data-notify-close', false)
            ->assertSee('notify--success');
    }

    public function test_login_shows_generic_success_notice_for_other_status_messages(): void
    {
        $this->withSession(['status' => 'Tu contraseña fue actualizada. Ya podés iniciar sesión.'])
            ->get(route('login.show'))
            ->assertOk()
            ->assertSee('Tu contraseña fue actualizada. Ya podés iniciar sesión.')
            ->assertDontSee('¡Correo verificado!');
    }

    public function test_login_validation_errors_render_as_alerts(): void
    {
        $this->from(route('login.show'))
            ->post(route('login.store'), [])
            ->assertRedirect(route('login.show'))
            ->assertSessionHasErrors(['email', 'password']);

        $this->get(route('login.show'))
            ->assertOk()
            ->assertSee('role="alert"', false)
            ->assertSee('field is required');
    }
}
