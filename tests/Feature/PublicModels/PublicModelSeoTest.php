<?php

namespace Tests\Feature\PublicModels;

use App\Models\PublicationType;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class PublicModelSeoTest extends TestCase
{
    use PublicModelProfileFixtures;

    public function test_profile_has_exact_public_title_description_and_one_h1(): void
    {
        $profile = $this->publicProfile();
        $type = PublicationType::factory()->create(['slug' => 'virtual', 'name' => 'Solo Virtual']);
        $profile->update(['publication_type_id' => $type->id]);

        $response = $this->get('/modelos/mia')->assertOk();
        $html = $response->getContent();

        $response->assertSee('<title>Mia | Divas Cuyo</title>', false)
            ->assertSee('<meta name="description" content="Perfil público de Mia · Solo Virtual · Mendoza · Ciudad de Mendoza · Zona centro.">', false)
            ->assertSee('<h1 id="profile-name">Mia</h1>', false);
        $this->assertSame(1, preg_match_all('/<h1\b/', $html));
    }

    public function test_metadata_escapes_the_public_name_and_excludes_hidden_age(): void
    {
        $profile = $this->publicProfile();
        $profile->forceFill([
            'stage_name' => 'Mia & <script>alert("x")</script>',
            'show_age' => false,
            'public_age' => 47,
        ])->save();

        $response = $this->get('/modelos/mia')->assertOk();

        $response->assertSee('<title>Mia &amp; &lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt; | Divas Cuyo</title>', false)
            ->assertDontSee('<script>alert("x")</script>', false);
        preg_match('/<meta name="description" content="([^"]*)">/', $response->getContent(), $matches);
        $this->assertArrayHasKey(1, $matches);
        $this->assertStringNotContainsString('47', $matches[1]);
        $this->assertStringNotContainsString('birth_date', $matches[1]);
    }

    public function test_hidden_profile_404_keeps_generic_metadata_without_profile_name(): void
    {
        $profile = $this->publicProfile();
        $profile->update(['is_published' => false]);

        $response = $this->get('/modelos/mia')->assertNotFound();

        $response->assertSee('<title>Página no encontrada · Divas Cuyo</title>', false)
            ->assertDontSee('Mia')
            ->assertDontSee('<meta name="description"', false);
    }
}
