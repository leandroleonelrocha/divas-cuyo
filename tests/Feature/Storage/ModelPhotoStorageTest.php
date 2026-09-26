<?php

namespace Tests\Feature\Storage;

use App\Services\ModelPhotoStorage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModelPhotoStorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['model-photos.disk' => 'model_photos']);
        Storage::fake('model_photos');
    }

    public function test_storage_uses_relative_random_paths_and_rejects_unsafe_paths(): void
    {
        $paths = app(ModelPhotoStorage::class)->storeVariants(1, [
            'original' => 'original',
            'processed' => 'processed',
            'public' => 'public',
            'thumbnail' => 'thumbnail',
        ], 'jpg');

        foreach ($paths as $path) {
            $this->assertStringStartsWith('model-profiles/', $path);
            $this->assertStringNotContainsString('..', $path);
            Storage::disk('model_photos')->assertExists($path);
        }

        $this->assertFalse(app(ModelPhotoStorage::class)->isSafePath('../private/file'));
        $this->assertFalse(app(ModelPhotoStorage::class)->isSafePath('/model-profiles/private/file'));
        $this->assertFalse(app(ModelPhotoStorage::class)->isSafePath('model-profiles\\private\\file'));
    }
}
