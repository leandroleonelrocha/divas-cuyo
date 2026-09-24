<?php

namespace Tests\Feature\Account;

use App\Models\ModelPhoto;
use App\Models\User;
use App\Services\ModelPhotoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModelPhotoDeletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['model-photos.disk' => 'model_photos']);
        Storage::fake('model_photos');
    }

    public function test_deleting_a_photo_removes_database_variants_and_compacts_positions(): void
    {
        $user = User::factory()->create();
        $first = $this->upload($user);
        $second = $this->upload($user);
        $first->update(['is_primary' => true]);
        $second->latestVersion->update(['status' => 'approved']);
        $second->update(['current_version_id' => $second->latestVersion->id]);
        $paths = $first->latestVersion->only(['original_path', 'processed_path', 'public_path', 'thumbnail_path']);

        $this->actingAs($user)
            ->delete(route('account.photos.destroy', $first))
            ->assertRedirect(route('account.photos.index'));

        $this->assertDatabaseMissing('model_photos', ['id' => $first->id]);
        foreach ($paths as $path) {
            Storage::disk('model_photos')->assertMissing($path);
        }
        $this->assertSame(0, $second->fresh()->position);
        $this->assertTrue($second->fresh()->is_primary);
    }

    public function test_deleting_the_only_pending_photo_leaves_the_profile_without_a_primary(): void
    {
        $user = User::factory()->create();
        $photo = $this->upload($user);

        $this->actingAs($user)->delete(route('account.photos.destroy', $photo));

        $this->assertDatabaseCount('model_photos', 0);
        $this->assertSame(0, ModelPhoto::query()->where('model_profile_id', $user->modelProfile->id)->where('is_primary', true)->count());
    }

    public function test_deletion_protects_ownership_and_path_traversal(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $photo = $this->upload($owner);

        $this->actingAs($other)
            ->delete(route('account.photos.destroy', $photo))
            ->assertForbidden();

        $photo->latestVersion->update(['original_path' => '../outside.jpg']);
        $this->expectException(\RuntimeException::class);
        app(ModelPhotoService::class)->deleteForAuthenticatedModel($owner, $photo->fresh());
        $this->assertDatabaseHas('model_photos', ['id' => $photo->id]);
    }

    private function upload(User $user): ModelPhoto
    {
        $this->actingAs($user)->post(route('account.photos.store'), [
            'photo' => UploadedFile::fake()->image('photo.jpg', 1000, 1000),
        ]);

        return ModelPhoto::query()->with('latestVersion')->latest('id')->firstOrFail();
    }
}
