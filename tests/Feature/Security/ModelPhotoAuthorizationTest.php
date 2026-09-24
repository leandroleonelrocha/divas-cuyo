<?php

namespace Tests\Feature\Security;

use App\Models\ModelPhoto;
use App\Models\User;
use App\Services\ModelPhotoModerationService;
use App\Services\ModelPhotoService;
use App\Services\ModelPhotoStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModelPhotoAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('model_photos');
    }

    public function test_guest_cannot_access_photo_management_or_private_variants(): void
    {
        [, $photo] = $this->uploadPhoto();
        auth()->logout();

        $this->get(route('account.photos.index'))->assertRedirect(route('login.show'));
        $this->get(route('account.photos.file', [$photo, 'thumbnail']))->assertRedirect(route('login.show'));
    }

    public function test_owner_endpoints_reject_cross_model_photo_ids(): void
    {
        [$owner, $photo] = $this->uploadPhoto();
        $other = User::factory()->create();

        $this->actingAs($other)->get(route('account.photos.file', [$photo, 'thumbnail']))->assertForbidden();
        $this->actingAs($other)->post(route('account.photos.primary', $photo))->assertForbidden();
        $this->actingAs($other)->post(route('account.photos.replace', $photo), [
            'photo' => UploadedFile::fake()->image('replacement.jpg', 1000, 1000),
        ])->assertForbidden();
        $this->actingAs($other)->delete(route('account.photos.destroy', $photo))->assertForbidden();

        $this->actingAs($owner)->patch(route('account.photos.order'), [
            'photo_ids' => [$photo->id, $other->modelProfile->photos()->create(['position' => 0])->id],
        ])->assertSessionHasErrors('photo_ids');
    }

    public function test_model_profile_id_is_ignored_and_cannot_redirect_upload_to_another_profile(): void
    {
        [$owner] = $this->uploadPhoto();
        $other = User::factory()->create();

        $this->actingAs($owner)->post(route('account.photos.store'), [
            'model_profile_id' => $other->modelProfile->id,
            'photo' => UploadedFile::fake()->image('photo.jpg', 1000, 1000),
        ])->assertRedirect();

        $this->assertSame(2, $owner->modelProfile->photos()->count());
        $this->assertSame(0, $other->modelProfile->photos()->count());
    }

    public function test_admin_version_id_must_belong_to_the_requested_photo(): void
    {
        [, $photo] = $this->uploadPhoto();
        [, $otherPhoto] = $this->uploadPhoto();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.model-photos.file', [$photo, $otherPhoto->latestVersion, 'thumbnail']))
            ->assertNotFound();
    }

    public function test_pending_and_non_approved_photos_cannot_become_primary(): void
    {
        [, $photo] = $this->uploadPhoto();

        $this->actingAs($photo->modelProfile->user)->post(route('account.photos.primary', $photo))
            ->assertSessionHasErrors('photo');
    }

    public function test_stale_approved_version_cannot_be_promoted_after_a_new_version_exists(): void
    {
        [$owner, $photo] = $this->uploadPhoto();
        $admin = User::factory()->create(['is_admin' => true]);
        app(ModelPhotoModerationService::class)->approve($photo, $admin);
        $photo->refresh();
        $current = $photo->currentVersion;

        $this->actingAs($owner)->post(route('account.photos.replace', $photo), [
            'photo' => UploadedFile::fake()->image('replacement.jpg', 1000, 1000),
        ]);

        $promoted = app(ModelPhotoService::class)->promoteApprovedVersion($current);

        $this->assertSame($current->id, $promoted->current_version_id);
        $this->assertSame($current->id, $photo->fresh()->current_version_id);
    }

    public function test_path_traversal_is_rejected_by_the_photo_storage(): void
    {
        $storage = app(ModelPhotoStorage::class);

        $this->assertFalse($storage->isSafePath('../outside.webp'));
        $this->assertFalse($storage->isSafePath('model-profiles/../outside.webp'));
        $this->assertFalse($storage->isSafePath("model-profiles/photo/\0.webp"));
        $this->assertFalse($storage->isSafePath('/model-profiles/photo.webp'));
    }

    /**
     * @return array{0: User, 1: ModelPhoto}
     */
    private function uploadPhoto(): array
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->post(route('account.photos.store'), [
            'photo' => UploadedFile::fake()->image('photo.jpg', 1000, 1000),
        ])->assertRedirect();

        return [$owner, ModelPhoto::query()->with('latestVersion')->latest('id')->firstOrFail()];
    }
}
