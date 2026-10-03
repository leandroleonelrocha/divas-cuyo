<?php

namespace Tests\Feature\PublicModels;

use App\Services\ModelProfileSlugService;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class PublicModelSlugRoutingTest extends TestCase
{
    use PublicModelProfileFixtures;

    public function test_aliases_redirect_directly_to_current_canonical_and_returning_to_old_name_has_no_loop(): void
    {
        $profile = $this->publicProfile();
        $service = app(ModelProfileSlugService::class);
        $service->rename($profile, 'Luna');
        $service->rename($profile, 'Sol');
        foreach (['mia', 'luna'] as $slug) {
            $response = $this->get('/modelos/'.$slug.'?redirect=https://evil.invalid')->assertStatus(302)
                ->assertRedirect(route('public.models.show', ['slug' => 'sol']));
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        }
        $response = $this->get('/modelos/sol')->assertOk();
        $this->assertSame(route('public.models.show', ['slug' => 'sol']), $response->viewData('publicProfile')->canonicalUrl);
        $service->rename($profile, 'Mia');
        $this->get('/modelos/mia')->assertOk();
        $this->get('/modelos/sol')->assertRedirect(route('public.models.show', ['slug' => 'mia']));
    }

    public function test_aliases_for_hidden_profiles_and_tombstones_are_generic_404_without_location(): void
    {
        $profile = $this->publicProfile();
        app(ModelProfileSlugService::class)->rename($profile, 'Luna');
        $profile->update(['is_published' => false]);
        $expected = $this->get('/modelos/missing')->assertNotFound()->getContent();
        foreach (['mia', 'luna', (string) $profile->id, 'MIA', 'mia--two', str_repeat('a', 161)] as $slug) {
            $response = $this->get('/modelos/'.$slug)->assertNotFound()->assertHeaderMissing('Location');
            $this->assertSame($expected, $response->getContent());
        }
        $this->actingAs($profile->user)->get('/modelos/mia')->assertNotFound()->assertHeaderMissing('Location');
        $profile->delete();
        $this->get('/modelos/mia')->assertNotFound()->assertHeaderMissing('Location');
    }

    public function test_missing_reservation_never_resolves_and_private_binding_stays_numeric(): void
    {
        $profile = $this->publicProfile();
        $profile->slugs()->delete();
        $this->get('/modelos/mia')->assertNotFound();
        $this->assertSame('id', $profile->getRouteKeyName());
    }

    public function test_alias_never_redirects_to_an_invalid_current_slug(): void
    {
        $profile = $this->publicProfile();
        $profile->forceFill(['slug' => '123'])->save();
        $this->get('/modelos/mia')->assertNotFound()->assertHeaderMissing('Location');
    }
}
