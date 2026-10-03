<?php

namespace Tests\Feature\PublicModels;

use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class PublicModelPresentationTest extends TestCase
{
    use PublicModelProfileFixtures;

    public function test_minimal_profile_uses_landmarks_public_image_alternatives_and_no_unavailable_actions(): void
    {
        $this->publicProfile();
        $response = $this->get('/modelos/mia')->assertOk();
        $html = $response->getContent();

        $this->assertMatchesRegularExpression('/<header\b/', $html);
        $this->assertMatchesRegularExpression('/<main\b[^>]*id="contenido-principal"/', $html);
        $this->assertSame(1, preg_match_all('/<h1\b/', $html));
        $this->assertMatchesRegularExpression('/<ul\b[^>]*class="photos-grid"[^>]*aria-label="Galería de fotografías"/', $html);
        $this->assertSame(1, preg_match_all('/<li\b[^>]*class="photo-card"/', $html));
        $this->assertSame(2, preg_match_all('/<img\b[^>]*\balt="Foto de Mia"/', $html));
        $this->assertStringNotContainsString('Características', $html);
        $this->assertStringNotContainsString('Sobre mí', $html);
        $this->assertStringNotContainsString('Modalidad', $html);
        $this->assertStringNotContainsString('Servicios', $html);
        foreach (['tel:', 'wa.me', 'whatsapp', 'Contacto', 'Mapa', 'href="#"'] as $unavailable) {
            $this->assertStringNotContainsString($unavailable, $html);
        }
    }

    public function test_not_found_page_keeps_generic_semantic_structure(): void
    {
        $response = $this->get('/modelos/no-existe')->assertNotFound();
        $html = $response->getContent();

        $this->assertMatchesRegularExpression('/<header\b/', $html);
        $this->assertMatchesRegularExpression('/<main\b/', $html);
        $this->assertSame(1, preg_match_all('/<h1\b/', $html));
        $response->assertSee('Página no encontrada')
            ->assertDontSee('no-existe')
            ->assertDontSee('Modelo verificada')
            ->assertDontSee('Disponible');
    }

    public function test_public_profile_reuses_the_home_front_header(): void
    {
        $this->publicProfile();
        $home = $this->get('/')->assertOk();
        $profile = $this->get('/modelos/mia')->assertOk();

        $homeHeader = preg_replace('/href="[^"]*"/', 'href=""', $this->frontHeader($home->getContent()));
        $profileHeader = preg_replace('/href="[^"]*"/', 'href=""', $this->frontHeader($profile->getContent()));

        $this->assertSame($homeHeader, $profileHeader);
    }

    private function frontHeader(string $html): string
    {
        $this->assertSame(1, preg_match('/<div class="dc-front-topbar"><\/div>.*?<\/header>/s', $html, $matches));

        return $matches[0];
    }
}
