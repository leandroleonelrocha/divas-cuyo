<?php

namespace Tests\Feature;

use App\Services\LegalAccess;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class LegalAccessTest extends TestCase
{
    use PublicModelProfileFixtures;

    public function test_new_visitors_cannot_read_public_pages_or_photo_bytes(): void
    {
        $profile = $this->publicProfile();
        $token = $profile->photos()->first()->currentVersion->public_token;
        foreach (['/', '/modelos/mia', '/modelos/fotos/'.$token, '/terminos-y-condiciones', '/politicas-del-sitio'] as $url) {
            $this->get($url)->assertRedirect(route('legal.show'))->assertDontSee('Mia');
        }
        $this->getJson('/modelos/mia')->assertForbidden()->assertDontSee('Mia');
        $this->get('/aviso-legal')->assertOk()->assertSee('Aviso legal y acceso para mayores de 18 años')
            ->assertSee('Ley Nacional N.º 26.842')->assertDontSee('dv-similar', false);
    }

    public function test_explicit_acceptance_sets_secure_cookie_and_restores_destination(): void
    {
        $this->publicProfile();
        $this->get('/modelos/mia')->assertRedirect(route('legal.show'));
        $response = $this->post('/aviso-legal', ['decision' => 'accept', 'version' => '1'])
            ->assertRedirect('/modelos/mia')->assertCookie(config('legal.cookie'));
        $cookie = $response->getCookie(config('legal.cookie'), false);
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, $cookie->getExpiresTime(), 2);
        $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue());
        $this->get('/modelos/mia')->assertOk()->assertSee('Mia');
        $this->get('/')->assertOk();
    }

    public function test_rejection_revokes_acceptance_and_blocks_later_requests(): void
    {
        $this->withCookie(config('legal.cookie'), app(LegalAccess::class)->cookie('accepted')->getValue());
        $response = $this->post('/aviso-legal', ['decision' => 'reject', 'version' => '1'])
            ->assertRedirect(route('legal.denied'));
        $cookie = $response->getCookie(config('legal.cookie'), false);
        $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue());
        $this->get('/acceso-denegado')->assertForbidden()->assertSee('Acceso denegado');
        $this->get('/')->assertRedirect(route('legal.denied'));
        $this->get('/modelos/mia')->assertRedirect(route('legal.denied'));
    }

    public function test_expired_changed_missing_and_tampered_acceptance_is_not_valid(): void
    {
        $cookie = app(LegalAccess::class)->cookie('accepted')->getValue();
        $this->withCookie(config('legal.cookie'), $cookie);
        $this->travel(31)->days();
        $this->get('/')->assertRedirect(route('legal.show'));
        $this->travelBack();
        config(['legal.version' => '2']);
        $this->get('/')->assertRedirect(route('legal.show'));
        config(['legal.version' => '1']);
        $this->withCookie(config('legal.cookie'), '');
        $this->get('/')->assertRedirect(route('legal.show'));
        $this->withUnencryptedCookie(config('legal.cookie'), $cookie);
        $this->get('/')->assertRedirect(route('legal.show'));
    }

    public function test_acceptance_requires_a_decision_and_the_current_version(): void
    {
        $this->post('/aviso-legal', [])->assertSessionHasErrors(['decision', 'version'])->assertCookieMissing(config('legal.cookie'));
        $this->post('/aviso-legal', ['decision' => 'accept', 'version' => 'old'])
            ->assertRedirect(route('legal.show'))->assertCookieMissing(config('legal.cookie'));
        $this->get('/aviso-legal?decision=accept')->assertCookieMissing(config('legal.cookie'));
    }

    public function test_duration_is_configurable_and_external_redirects_are_rejected(): void
    {
        config(['legal.days' => 7]);
        $response = $this->withSession(['legal.destination' => '//evil.example'])
            ->post('/aviso-legal', ['decision' => 'accept', 'version' => '1'])->assertRedirect('/');
        $this->assertEqualsWithDelta(now()->addDays(7)->timestamp, $response->getCookie(config('legal.cookie'))->getExpiresTime(), 2);
    }

    public function test_authentication_and_administration_are_not_gated(): void
    {
        foreach (['/login', '/registro', '/password/forgot', '/admin/login'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/account')->assertRedirect(route('login.show'));
    }
}
