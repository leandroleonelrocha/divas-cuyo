<?php

namespace Tests\Feature\PublicModels;

use App\Services\ModelPhotoImageProcessor;
use App\Services\ModelPhotoStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class PreparePublicModelPhotosTest extends TestCase
{
    use PublicModelProfileFixtures;

    private function legacy()
    {
        $profile = $this->publicProfile();
        $version = $profile->photos()->first()->currentVersion;
        $processed = app(ModelPhotoImageProcessor::class)->process(UploadedFile::fake()->image('source.jpg', 800, 800));
        Storage::disk('model_photos')->put($version->processed_path, $processed['contents']['processed']);
        $version->forceFill(['public_watermarked_at' => null])->save();

        return $version;
    }

    public function test_dry_run_does_not_write_and_preparation_is_idempotent_preserving_private_source(): void
    {
        $version = $this->legacy();
        $before = $version->fresh()->getAttributes();
        $photoBefore = $version->photo->getAttributes();
        $profileBefore = $version->photo->modelProfile->getAttributes();
        $files = Storage::disk('model_photos')->allFiles();
        $source = Storage::disk('model_photos')->get($version->processed_path);
        $this->artisan('model-photos:prepare-public --dry-run')->assertExitCode(0);
        $this->assertSame($before, $version->fresh()->getAttributes());
        $this->assertSame($files, Storage::disk('model_photos')->allFiles());
        $this->artisan('model-photos:prepare-public')->assertExitCode(0);
        $ready = $version->fresh();
        $this->assertNotNull($ready->public_watermarked_at);
        $this->assertNotSame($version->public_path, $ready->public_path);
        $this->assertSame($source, Storage::disk('model_photos')->get($ready->processed_path));
        $this->assertSame(IMAGETYPE_WEBP, getimagesizefromstring(Storage::disk('model_photos')->get($ready->public_path))[2]);
        $this->assertSame($version->id, $ready->photo->current_version_id);
        $this->assertSame('approved', $ready->status->value);
        $this->assertSame($photoBefore, $ready->photo->getAttributes());
        $this->assertSame($profileBefore, $ready->photo->modelProfile->getAttributes());
        $this->artisan('model-photos:prepare-public')->assertExitCode(0);
        $this->assertSame($ready->getAttributes(), $version->fresh()->getAttributes());
    }

    public function test_missing_source_or_disabled_watermark_does_not_certify(): void
    {
        $version = $this->legacy();
        config(['model-photos.watermark.enabled' => false]);
        $this->artisan('model-photos:prepare-public')->assertExitCode(1);
        $this->assertNull($version->fresh()->public_watermarked_at);
        config(['model-photos.watermark.enabled' => true]);
        Storage::disk('model_photos')->delete($version->processed_path);
        $this->artisan('model-photos:prepare-public')->assertExitCode(1);
        $this->assertNull($version->fresh()->public_watermarked_at);
    }

    public function test_storage_failure_preserves_existing_files_and_evidence(): void
    {
        $version = $this->legacy();
        $files = Storage::disk('model_photos')->allFiles();
        Log::shouldReceive('warning')->once()->with('public_photo_preparation_failed', ['version_id' => $version->id]);
        $this->partialMock(ModelPhotoStorage::class, function ($mock) {
            $mock->shouldReceive('storePublicVariant')->andThrow(new \RuntimeException('private storage path'));
        });
        $this->artisan('model-photos:prepare-public')->assertExitCode(1);
        $this->assertNull($version->fresh()->public_watermarked_at);
        $this->assertSame($files, Storage::disk('model_photos')->allFiles());
    }

    public function test_changed_current_version_discards_only_new_resource(): void
    {
        $version = $this->legacy();
        $files = Storage::disk('model_photos')->allFiles();
        $real = new ModelPhotoStorage;
        $this->partialMock(ModelPhotoStorage::class, function ($mock) use ($version, $real) {
            $mock->shouldReceive('storePublicVariant')->andReturnUsing(function ($path, $contents) use ($version, $real) {
                $new = $real->storePublicVariant($path, $contents);
                $version->photo->update(['current_version_id' => null]);

                return $new;
            });
        });
        $this->artisan('model-photos:prepare-public')->assertExitCode(1);
        $this->assertNull($version->fresh()->public_watermarked_at);
        $this->assertSame($files, Storage::disk('model_photos')->allFiles());
    }

    public function test_noncurrent_pending_and_rejected_versions_are_not_prepared(): void
    {
        $version = $this->legacy();
        $historical = $version->replicate(['public_token']);
        $historical->version = 2;
        $historical->save();
        $this->artisan('model-photos:prepare-public')->assertExitCode(0);
        $this->assertNull($historical->fresh()->public_watermarked_at);
        foreach (['pending', 'rejected'] as $status) {
            $version->forceFill(['status' => $status, 'public_watermarked_at' => null])->save();
            $before = $version->fresh()->getAttributes();
            $this->artisan('model-photos:prepare-public')->assertExitCode(0);
            $this->assertSame($before, $version->fresh()->getAttributes());
        }
    }
}
