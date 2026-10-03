<?php

namespace Tests\Feature\PublicModels;

use App\Services\ModelPhotoStorage;
use App\Services\PublicModelProfileVisibility;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class PublicModelVisibilityRuleTest extends TestCase
{
    use PublicModelProfileFixtures;

    public static function invalidPrimary(): array
    {
        return array_map(fn ($case) => [$case], ['no-primary', 'no-current', 'pending', 'rejected', 'unmarked', 'unsafe', 'missing', 'empty', 'foreign']);
    }

    #[DataProvider('invalidPrimary')]
    public function test_principal_must_be_current_owned_approved_marked_and_readable(string $case): void
    {
        $profile = $this->publicProfile();
        $photo = $profile->photos()->first();
        $version = $photo->currentVersion;
        match ($case) {
            'no-primary' => $photo->update(['is_primary' => false]),
            'no-current' => $photo->update(['current_version_id' => null]),
            'pending', 'rejected' => $version->update(['status' => $case]),
            'unmarked' => $version->forceFill(['public_watermarked_at' => null])->save(),
            'unsafe' => $version->update(['public_path' => '../secret.webp']),
            'missing' => Storage::disk('model_photos')->delete($version->public_path),
            'empty' => Storage::disk('model_photos')->put($version->public_path, ''),
            'foreign' => $version->update(['model_photo_id' => $profile->photos()->create(['position' => 1])->id]),
        };
        $this->assertFalse(app(PublicModelProfileVisibility::class)->publiclyVisible($profile));
        $this->get('/modelos/mia')->assertNotFound();
    }

    public function test_gate_allows_guests_and_uses_current_database_state(): void
    {
        $profile = $this->publicProfile();
        $this->assertTrue(Gate::allows('viewPublicModelProfile', $profile));
        $profile->user->forceFill(['email_verified_at' => null])->save();
        $this->assertFalse(Gate::allows('viewPublicModelProfile', $profile));
    }

    public function test_storage_read_error_is_not_exposed(): void
    {
        $profile = $this->publicProfile();
        $this->partialMock(ModelPhotoStorage::class, function ($mock) {
            $mock->shouldReceive('isSafePath')->andReturn(true);
            $mock->shouldReceive('readStream')->andThrow(new \RuntimeException('private-storage-secret'));
        });
        $this->get('/modelos/mia')->assertNotFound()->assertDontSee('private-storage-secret');
    }
}
