<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ModelProfiles\ModelProfileResource;
use App\Filament\Resources\ModelProfiles\RelationManagers\ModelPhotosRelationManager;
use App\Models\ModelPhoto;
use App\Models\User;
use App\Services\ModelPhotoModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ModelPhotoModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('model_photos');
    }

    public function test_only_a_verified_admin_can_open_private_photo_variants(): void
    {
        [$owner, $photo] = $this->uploadPhoto();
        $admin = User::factory()->create(['is_admin' => true]);
        $regularUser = User::factory()->create(['is_admin' => false]);
        $unverifiedAdmin = User::factory()->unverified()->create(['is_admin' => true]);
        $version = $photo->latestVersion;

        $url = route('admin.model-photos.file', [$photo, $version, 'thumbnail']);

        $this->actingAs($admin)->get($url)->assertOk();
        $this->actingAs($regularUser)->get($url)->assertForbidden();
        $this->actingAs($unverifiedAdmin)->get($url)->assertForbidden();
        $this->actingAs($owner)->get($url)->assertForbidden();
        $this->assertContains($this->get($url)->status(), [302, 403]);
    }

    public function test_admin_can_approve_pending_photo_and_audit_the_version(): void
    {
        [, $photo] = $this->uploadPhoto();
        $admin = User::factory()->create(['is_admin' => true]);
        $profile = $photo->modelProfile;
        $before = $profile->only(['identity_status', 'review_status', 'is_published']);

        app(ModelPhotoModerationService::class)->approve($photo, $admin);

        $photo->refresh();
        $version = $photo->currentVersion()->first();
        $this->assertSame('approved', $version->status->value);
        $this->assertSame($admin->id, $version->reviewed_by);
        $this->assertNotNull($version->reviewed_at);
        $this->assertNull($version->rejection_reason);
        $this->assertSame($before, $profile->fresh()->only(['identity_status', 'review_status', 'is_published']));
    }

    public function test_reject_requires_reason_and_records_audit(): void
    {
        [, $photo] = $this->uploadPhoto();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->expectException(ValidationException::class);
        app(ModelPhotoModerationService::class)->reject($photo, $admin, '  ');
    }

    public function test_rejection_records_reason_without_changing_profile_states(): void
    {
        [, $photo] = $this->uploadPhoto();
        $admin = User::factory()->create(['is_admin' => true]);
        $profile = $photo->modelProfile;
        $before = $profile->only(['identity_status', 'review_status', 'is_published']);

        app(ModelPhotoModerationService::class)->reject($photo, $admin, 'La imagen no cumple con los requisitos.');

        $version = $photo->latestVersion()->first();
        $this->assertSame('rejected', $version->status->value);
        $this->assertSame('La imagen no cumple con los requisitos.', $version->rejection_reason);
        $this->assertSame($admin->id, $version->reviewed_by);
        $this->assertNotNull($version->reviewed_at);
        $this->assertSame($before, $profile->fresh()->only(['identity_status', 'review_status', 'is_published']));
    }

    public function test_approved_replacement_shows_both_versions_and_promotes_only_after_approval(): void
    {
        [$owner, $photo] = $this->uploadPhoto();
        $admin = User::factory()->create(['is_admin' => true]);
        app(ModelPhotoModerationService::class)->approve($photo, $admin);

        $photo->refresh();
        $oldVersion = $photo->currentVersion;
        $this->actingAs($owner)->post(route('account.photos.replace', $photo), [
            'photo' => UploadedFile::fake()->image('replacement.jpg', 1000, 1000),
        ])->assertRedirect();
        $photo->refresh();
        $pendingVersion = $photo->latestVersion;

        $this->actingAs($admin)
            ->get(route('admin.model-photos.file', [$photo, $oldVersion, 'thumbnail']))
            ->assertOk();
        $this->actingAs($admin)
            ->get(route('admin.model-photos.file', [$photo, $pendingVersion, 'thumbnail']))
            ->assertOk();
        $this->assertSame($oldVersion->id, $photo->current_version_id);

        app(ModelPhotoModerationService::class)->approve($photo, $admin);

        $photo->refresh();
        $this->assertSame($pendingVersion->id, $photo->current_version_id);
        $this->assertFalse($photo->is_primary);
        $this->assertDatabaseMissing('model_photo_versions', ['id' => $oldVersion->id]);
    }

    public function test_rejecting_approved_replacement_keeps_the_current_version(): void
    {
        [$owner, $photo] = $this->uploadPhoto();
        $admin = User::factory()->create(['is_admin' => true]);
        app(ModelPhotoModerationService::class)->approve($photo, $admin);
        $photo->refresh();
        $currentId = $photo->current_version_id;

        $this->actingAs($owner)->post(route('account.photos.replace', $photo), [
            'photo' => UploadedFile::fake()->image('replacement.jpg', 1000, 1000),
        ]);
        app(ModelPhotoModerationService::class)->reject($photo, $admin, 'Reemplazo rechazado.');

        $photo->refresh();
        $this->assertSame($currentId, $photo->current_version_id);
        $this->assertSame('rejected', $photo->latestVersion->status->value);
        $this->assertDatabaseHas('model_photo_versions', ['id' => $currentId, 'status' => 'approved']);
    }

    public function test_moderation_action_is_not_available_for_a_stale_non_pending_photo(): void
    {
        [, $photo] = $this->uploadPhoto();
        $admin = User::factory()->create(['is_admin' => true]);
        app(ModelPhotoModerationService::class)->approve($photo, $admin);

        $this->expectException(ValidationException::class);
        app(ModelPhotoModerationService::class)->approve($photo->fresh(), $admin);
    }

    public function test_model_profile_detail_registers_the_photo_relation_manager(): void
    {
        $this->assertSame(ModelPhotosRelationManager::class, ModelProfileResource::getRelations()[0]);
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

        return [$owner, ModelPhoto::query()->with(['latestVersion', 'modelProfile'])->firstOrFail()];
    }
}
