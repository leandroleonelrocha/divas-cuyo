<?php

namespace Tests\Feature\PublicModels;

use App\Models\ModelProfileSlug;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class PublicModelRevocationTest extends TestCase
{
    use PublicModelProfileFixtures;

    public function test_alias_and_photo_requests_are_revoked_without_cache_or_existence_hints(): void
    {
        $profile = $this->publicProfile('mia');
        $profile->forceFill(['slug' => 'mia-final'])->save();
        (new ModelProfileSlug)->forceFill([
            'slug' => 'mia-final',
            'model_profile_id' => $profile->id,
        ])->save();
        $version = $profile->photos()->first()->currentVersion;

        $this->get('/modelos/mia', ['If-None-Match' => '"previous-profile"'])
            ->assertRedirect('/modelos/mia-final')
            ->assertHeaderMissing('ETag');
        $this->get('/modelos/fotos/'.$version->public_token, ['If-None-Match' => '"previous-photo"'])
            ->assertOk()
            ->assertHeaderMissing('ETag')
            ->streamedContent();

        $profile->update(['is_published' => false]);
        $expected = $this->get('/modelos/not-found')->assertNotFound()->getContent();
        $alias = $this->get('/modelos/mia')->assertNotFound()->assertHeaderMissing('Location');
        $image = $this->get('/modelos/fotos/'.$version->public_token)->assertNotFound();

        $this->assertSame($expected, $alias->getContent());
        $this->assertSame($expected, $image->getContent());
        $this->assertStringContainsString('no-store', $alias->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $image->headers->get('Cache-Control'));
        $image->assertHeaderMissing('ETag');
    }
}
