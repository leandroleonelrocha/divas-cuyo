<?php

namespace Tests\Feature\PublicModels;

use App\Models\ModelPhotoVersion;
use App\Models\ModelProfile;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class PublicModelSchemaTest extends TestCase
{
    use PublicModelProfileFixtures;

    public function test_existing_accounts_can_remain_without_a_public_slug(): void
    {
        $first = User::factory()->create()->modelProfile;
        $second = User::factory()->create()->modelProfile;
        $this->assertNull($first->fresh()->slug);
        $this->assertNull($second->fresh()->slug);
        $this->get('/modelos/'.$first->id)->assertNotFound();
    }

    public function test_slug_is_unique(): void
    {
        $this->publicProfile();
        $other = User::factory()->create()->modelProfile;
        $this->expectException(UniqueConstraintViolationException::class);
        $other->forceFill(['slug' => 'mia'])->save();
    }

    public function test_photo_token_unique_and_backfill_does_not_certify_legacy(): void
    {
        $profile = $this->publicProfile();
        $version = $profile->photos()->first()->currentVersion;
        $version->forceFill(['public_token' => null, 'public_watermarked_at' => null])->save();
        $migration = require database_path('migrations/2026_09_29_000006_backfill_public_photo_tokens.php');
        $migration->up();
        $token = $version->fresh()->public_token;
        $this->assertTrue(Str::isUuid($token));
        $this->assertNull($version->fresh()->public_watermarked_at);
        $migration->up();
        $this->assertSame($token, $version->fresh()->public_token);
        $duplicate = $version->fresh()->replicate();
        $duplicate->version = 2;
        $this->expectException(UniqueConstraintViolationException::class);
        $duplicate->save();
    }

    public function test_public_metadata_cannot_be_mass_assigned_and_is_not_inferred(): void
    {
        $this->assertFalse((new ModelProfile)->isFillable('slug'));
        $this->assertFalse((new ModelPhotoVersion)->isFillable('public_watermarked_at'));
        $this->assertFalse((new ModelPhotoVersion)->isFillable('public_token'));
        $profile = $this->publicProfile();
        $version = $profile->photos()->first()->currentVersion;
        $legacy = $version->replicate(['public_token']);
        unset($legacy->public_watermarked_at);
        $legacy->version = 2;
        $legacy->save();
        $this->assertNull($legacy->fresh()->public_watermarked_at);
    }
}
