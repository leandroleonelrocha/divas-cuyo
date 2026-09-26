<?php

namespace Tests\Feature\Account;

use App\Models\ModelPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModelPhotoUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['model-photos.disk' => 'model_photos']);
        Storage::fake('model_photos');
    }

    public function test_verified_model_can_upload_jpeg_png_and_webp_as_pending(): void
    {
        $user = User::factory()->create();

        foreach (['jpeg' => 'photo.jpg', 'png' => 'photo.png', 'webp' => 'photo.webp'] as $extension => $name) {
            $response = $this->actingAs($user)->post(route('account.photos.store'), [
                'photo' => UploadedFile::fake()->image($name, 1000, 1000),
            ]);

            $response->assertRedirect(route('account.photos.index'));
        }

        $this->assertDatabaseCount('model_photos', 3);
        $this->assertDatabaseHas('model_photo_versions', ['status' => 'pending', 'mime_type' => 'image/jpeg']);
        $this->assertSame(3, ModelPhoto::query()->where('model_profile_id', $user->modelProfile->id)->count());
    }

    public function test_invalid_mime_and_unsupported_files_are_rejected(): void
    {
        $user = User::factory()->create();

        foreach ([
            UploadedFile::fake()->create('video.mp4', 10, 'video/mp4'),
            UploadedFile::fake()->create('fake.jpg', 10, 'image/jpeg'),
        ] as $file) {
            $this->actingAs($user)
                ->post(route('account.photos.store'), ['photo' => $file])
                ->assertSessionHasErrors('photo');
        }

        $this->assertDatabaseCount('model_photos', 0);
    }

    public function test_size_and_dimension_limits_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('account.photos.store'), [
            'photo' => UploadedFile::fake()->create('large.jpg', 5121, 'image/jpeg'),
        ])->assertSessionHasErrors('photo');

        $this->actingAs($user)
            ->post(route('account.photos.store'), [
                'photo' => UploadedFile::fake()->image('large.jpg', 10001, 10001),
            ])
            ->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('model_photos', 0);
    }

    public function test_maximum_of_five_photos_is_enforced(): void
    {
        $user = User::factory()->create();

        for ($index = 0; $index < 5; $index++) {
            $this->actingAs($user)->post(route('account.photos.store'), [
                'photo' => UploadedFile::fake()->image("photo-{$index}.jpg", 800, 800),
            ])->assertRedirect();
        }

        $this->actingAs($user)
            ->post(route('account.photos.store'), ['photo' => UploadedFile::fake()->image('sixth.jpg', 800, 800)])
            ->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('model_photos', 5);
    }

    public function test_upload_resolves_ownership_from_authenticated_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($owner)->post(route('account.photos.store'), [
            'model_profile_id' => $other->modelProfile->id,
            'photo' => UploadedFile::fake()->image('photo.jpg', 1000, 1000),
        ])->assertRedirect();

        $this->assertDatabaseHas('model_photos', [
            'model_profile_id' => $owner->modelProfile->id,
        ]);
        $this->assertDatabaseMissing('model_photos', [
            'model_profile_id' => $other->modelProfile->id,
        ]);
    }

    public function test_variants_are_private_and_original_is_never_served(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('account.photos.store'), [
            'photo' => UploadedFile::fake()->image('photo.jpg', 1200, 1000),
        ]);

        $photo = ModelPhoto::query()->with('latestVersion')->firstOrFail();
        $version = $photo->latestVersion;
        $disk = Storage::disk('model_photos');

        $disk->assertExists($version->original_path);
        $disk->assertExists($version->processed_path);
        $disk->assertExists($version->public_path);
        $disk->assertExists($version->thumbnail_path);
        $this->assertStringStartsNotWith(public_path(), $version->original_path);
        $this->assertNotSame($disk->get($version->processed_path), $disk->get($version->public_path));

        $this->actingAs($user)
            ->get(route('account.photos.file', [$photo, 'original']))
            ->assertNotFound();
        $publicStorageResponse = $this->get('/storage/'.$version->original_path);
        $this->assertContains($publicStorageResponse->status(), [403, 404]);
    }

    public function test_owner_can_see_gallery_and_pending_thumbnail_only_through_authorized_route(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($owner)->post(route('account.photos.store'), [
            'photo' => UploadedFile::fake()->image('photo.jpg', 1000, 1000),
        ]);
        $photo = ModelPhoto::query()->firstOrFail();

        $this->actingAs($owner)->get(route('account.photos.index'))->assertOk();
        $this->actingAs($owner)->get(route('account.photos.file', [$photo, 'thumbnail']))->assertOk();
        $this->actingAs($other)->get(route('account.photos.file', [$photo, 'thumbnail']))->assertForbidden();
        $this->app['auth']->logout();
        $this->get(route('account.photos.index'))->assertRedirect(route('login.show'));
    }
}
