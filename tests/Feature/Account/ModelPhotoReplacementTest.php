<?php

namespace Tests\Feature\Account;

use App\Models\ModelPhoto;
use App\Models\User;
use App\Services\ModelPhotoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModelPhotoReplacementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['model-photos.disk' => 'model_photos']);
        Storage::fake('model_photos');
    }

    public function test_pending_and_rejected_photos_receive_a_new_pending_version(): void
    {
        $user = User::factory()->create();
        $pending = $this->upload($user);
        $rejected = $this->upload($user);
        $rejected->latestVersion()->update([
            'status' => 'rejected',
            'rejection_reason' => 'Imagen anterior no válida.',
            'reviewed_at' => now(),
        ]);

        foreach ([$pending, $rejected] as $photo) {
            $this->actingAs($user)
                ->post(route('account.photos.replace', $photo), ['photo' => UploadedFile::fake()->image('replacement.jpg', 1000, 1000)])
                ->assertRedirect(route('account.photos.index'));

            $latest = $photo->fresh(['latestVersion'])->latestVersion;
            $this->assertSame('pending', $latest->status->value);
            $this->assertNull($latest->rejection_reason);
            $this->assertNull($latest->reviewed_at);
            $this->assertNull($latest->reviewed_by);
        }
    }

    public function test_approved_replacement_keeps_the_previous_current_version_until_promotion(): void
    {
        $user = User::factory()->create();
        $photo = $this->upload($user);
        $oldVersion = $photo->latestVersion;
        $oldVersion->update(['status' => 'approved']);
        $photo->update(['current_version_id' => $oldVersion->id, 'is_primary' => true]);
        $oldPaths = [$oldVersion->original_path, $oldVersion->processed_path, $oldVersion->public_path, $oldVersion->thumbnail_path];

        $this->actingAs($user)
            ->post(route('account.photos.replace', $photo), ['photo' => UploadedFile::fake()->image('replacement.jpg', 1000, 1000)])
            ->assertRedirect(route('account.photos.index'));

        $photo->refresh()->load(['currentVersion', 'latestVersion']);
        $newVersion = $photo->latestVersion;
        $this->assertSame($oldVersion->id, $photo->current_version_id);
        $this->assertSame('approved', $photo->currentVersion->status->value);
        $this->assertSame('pending', $newVersion->status->value);
        $this->assertTrue($photo->is_primary);
        foreach ($oldPaths as $path) {
            Storage::disk('model_photos')->assertExists($path);
        }

        $newVersion->update(['status' => 'approved']);
        app(ModelPhotoService::class)->promoteApprovedVersion($newVersion->fresh());

        $photo->refresh();
        $this->assertSame($newVersion->id, $photo->current_version_id);
        $this->assertTrue($photo->is_primary);
        $this->assertDatabaseMissing('model_photo_versions', ['id' => $oldVersion->id]);
        foreach ($oldPaths as $path) {
            Storage::disk('model_photos')->assertMissing($path);
        }
    }

    public function test_rejected_new_version_does_not_break_the_previous_approved_version(): void
    {
        $user = User::factory()->create();
        $photo = $this->upload($user);
        $oldVersion = $photo->latestVersion;
        $oldVersion->update(['status' => 'approved']);
        $photo->update(['current_version_id' => $oldVersion->id]);

        $this->actingAs($user)->post(route('account.photos.replace', $photo), [
            'photo' => UploadedFile::fake()->image('replacement.jpg', 1000, 1000),
        ]);
        $newVersion = $photo->fresh(['latestVersion'])->latestVersion;
        $newVersion->update(['status' => 'rejected', 'rejection_reason' => 'Corregí la imagen.']);

        $photo->refresh()->load('currentVersion');
        $this->assertSame($oldVersion->id, $photo->current_version_id);
        $this->assertSame('approved', $photo->currentVersion->status->value);
        $this->assertSame('Corregí la imagen.', $newVersion->fresh()->rejection_reason);
    }

    public function test_failed_replacement_keeps_the_previous_version_and_does_not_create_a_new_one(): void
    {
        $user = User::factory()->create();
        $photo = $this->upload($user);
        $oldVersionId = $photo->latestVersion->id;

        $this->actingAs($user)->post(route('account.photos.replace', $photo), [
            'photo' => UploadedFile::fake()->create('invalid.jpg', 10, 'image/jpeg'),
        ])->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('model_photo_versions', 1);
        $this->assertDatabaseHas('model_photo_versions', ['id' => $oldVersionId, 'status' => 'pending']);
    }

    private function upload(User $user): ModelPhoto
    {
        $this->actingAs($user)->post(route('account.photos.store'), [
            'photo' => UploadedFile::fake()->image('photo.jpg', 1000, 1000),
        ]);

        return ModelPhoto::query()->latest('id')->firstOrFail();
    }
}
