<?php

namespace Tests\Feature\ModelPhotos;

use App\Models\ModelPhoto;
use App\Models\ModelPhotoVersion;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ModelPhotoSchemaTest extends TestCase
{
    public function test_model_photo_schema_and_relations_are_available(): void
    {
        $this->assertTrue(Schema::hasTable('model_photos'));
        $this->assertTrue(Schema::hasColumns('model_photos', [
            'model_profile_id', 'current_version_id', 'position', 'is_primary', 'created_at', 'updated_at',
        ]));
        $this->assertTrue(Schema::hasColumns('model_photo_versions', [
            'model_photo_id', 'version', 'original_path', 'processed_path', 'public_path', 'thumbnail_path',
            'status', 'reviewed_at', 'reviewed_by',
        ]));

        $user = User::factory()->create();
        $photo = ModelPhoto::query()->create([
            'model_profile_id' => $user->modelProfile->id,
            'position' => 0,
            'is_primary' => false,
        ]);
        $version = $photo->versions()->create([
            'version' => 1,
            'original_path' => 'model-profiles/a/photos/b/c/original.jpg',
            'processed_path' => 'model-profiles/a/photos/b/c/processed.webp',
            'public_path' => 'model-profiles/a/photos/b/c/public.webp',
            'thumbnail_path' => 'model-profiles/a/photos/b/c/thumbnail.webp',
            'mime_type' => 'image/jpeg',
            'file_size' => 100,
            'width' => 800,
            'height' => 800,
            'processed_width' => 800,
            'processed_height' => 800,
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(ModelPhotoVersion::class, $photo->versions()->first());
        $this->assertSame($photo->id, $version->photo->id);
        $this->assertSame($user->modelProfile->id, $photo->modelProfile->id);
    }
}
