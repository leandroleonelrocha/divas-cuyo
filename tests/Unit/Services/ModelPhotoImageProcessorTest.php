<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\ModelPhotoImageProcessor;
use App\Services\ModelPhotoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ModelPhotoImageProcessorTest extends TestCase
{
    public function test_processor_generates_normalized_thumbnail_and_watermarked_variants(): void
    {
        $this->assertSame('public/images/watermark-divas-cuyo.png', config('model-photos.watermark.asset'));

        $result = app(ModelPhotoImageProcessor::class)->process(
            UploadedFile::fake()->image('photo.jpg', 3000, 1600),
        );

        $this->assertTrue($result['public_watermarked']);
        $this->assertSame('image/jpeg', $result['mime_type']);
        $this->assertSame('jpg', $result['extension']);
        $this->assertLessThanOrEqual(2000, max($result['processed_width'], $result['processed_height']));
        $this->assertNotSame($result['contents']['processed'], $result['contents']['public']);
        $this->assertNotEmpty($result['contents']['original']);
        $this->assertNotEmpty($result['contents']['thumbnail']);
    }

    public function test_disabled_watermark_does_not_accredit_public_image_or_change_private_variants(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 800, 800);
        $marked = app(ModelPhotoImageProcessor::class)->process($file);
        config(['model-photos.watermark.enabled' => false]);
        $plain = app(ModelPhotoImageProcessor::class)->process($file);
        $this->assertFalse($plain['public_watermarked']);
        foreach (['original', 'processed', 'thumbnail'] as $variant) {
            $this->assertSame($marked['contents'][$variant], $plain['contents'][$variant]);
        }
        $this->assertNotSame($marked['contents']['public'], $plain['contents']['public']);
    }

    public function test_processor_rejects_a_file_with_false_mime_or_invalid_content(): void
    {
        $this->expectException(ValidationException::class);

        app(ModelPhotoImageProcessor::class)->process(
            UploadedFile::fake()->create('fake.jpg', 10, 'image/jpeg'),
        );
    }

    public function test_invisible_watermark_is_not_accredited(): void
    {
        config(['model-photos.watermark.opacity' => 0]);
        $result = app(ModelPhotoImageProcessor::class)->process(UploadedFile::fake()->image('photo.jpg', 800, 800));
        $this->assertFalse($result['public_watermarked']);
    }

    public function test_upload_only_persists_watermark_evidence_after_generation_and_storage(): void
    {
        Storage::fake('model_photos');
        $user = User::factory()->create();
        $service = app(ModelPhotoService::class);
        $photo = $service->upload($user, UploadedFile::fake()->image('photo.jpg', 800, 800));
        $version = $photo->latestVersion;
        $this->assertTrue(Str::isUuid($version->public_token));
        $this->assertNotNull($version->public_watermarked_at);
        $this->assertSame('pending', $version->status->value);
        Storage::disk('model_photos')->assertExists($version->public_path);
        config(['model-photos.watermark.enabled' => false]);
        $plain = $service->upload($user, UploadedFile::fake()->image('plain.jpg', 800, 800));
        $this->assertNull($plain->latestVersion->public_watermarked_at);
        config(['model-photos.watermark.enabled' => true, 'model-photos.watermark.asset' => 'missing.png']);
        try {
            $service->upload($user, UploadedFile::fake()->image('broken.jpg', 800, 800));
            $this->fail('Invalid watermark must fail generation.');
        } catch (ValidationException) {
            $this->assertSame(2, $user->modelProfile->photos()->count());
        }
    }
}
