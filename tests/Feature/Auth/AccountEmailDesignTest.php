<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyModelEmail;
use Illuminate\Auth\Notifications\ResetPassword;
use Tests\TestCase;

class AccountEmailDesignTest extends TestCase
{
    public function test_verification_email_renders_brand_spanish_copy_and_verification_url(): void
    {
        $user = new User(['name' => '<script>alert(1)</script>', 'email' => 'modelo@example.com']);
        $message = (new VerifyModelEmail('preview-token'))->toMail($user);
        $html = view('emails.account', $message->data())->render();
        $text = view('emails.account-text', $message->data())->render();

        $this->assertStringContainsString('DIVAS', $html);
        $this->assertStringContainsString('#d71920', $html);
        $this->assertStringContainsString('Confirmá tu correo', $html);
        $this->assertStringContainsString('El enlace vence en 24 horas.', $html);
        $this->assertStringContainsString('Si el botón no funciona', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertSame(route('verification.verify', ['token' => 'preview-token']), $message->actionUrl);
        $this->assertStringContainsString($message->actionUrl, $html);
        $this->assertStringContainsString($message->actionUrl, $text);
        $this->assertStringNotContainsString('Regards', $html);
    }

    public function test_password_reset_email_uses_brand_and_configured_expiry(): void
    {
        config()->set('auth.passwords.'.config('auth.defaults.passwords').'.expire', 45);
        $user = new User(['name' => 'Modelo', 'email' => 'modelo+cuenta@example.com']);
        $message = (new ResetPassword('preview-reset-token'))->toMail($user);
        $html = view('emails.account', $message->data())->render();
        $text = view('emails.account-text', $message->data())->render();

        $this->assertSame(['html' => 'emails.account', 'text' => 'emails.account-text'], $message->view);
        $this->assertSame('Restablecé tu contraseña · Divas Cuyo', $message->subject);
        $this->assertStringContainsString('Recuperá tu contraseña', $html);
        $this->assertStringContainsString('45 minutos', $html);
        $this->assertStringContainsString('Equipo Divas Cuyo', $text);
        $this->assertSame(route('password.reset', ['token' => 'preview-reset-token', 'email' => $user->email]), $message->actionUrl);
        $this->assertStringContainsString(e($message->actionUrl), $html);
        $this->assertStringContainsString($message->actionUrl, $text);
    }
}
