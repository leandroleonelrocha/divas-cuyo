<?php

namespace Tests\Feature\Security;

use App\Models\ModelPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModelPhotoPrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('model_photos');
    }

    public function test_private_variants_have_no_public_storage_url_and_use_private_headers(): void
    {
        [$owner, $photo] = $this->uploadPhoto();
        $version = $photo->latestVersion;
        $disk = Storage::disk('model_photos');

        $this->assertStringNotContainsString(public_path(), $version->original_path);
        $this->assertStringNotContainsString('/storage/', $version->original_path);
        $this->assertStringNotContainsString($version->original_path, $owner->modelProfile->toJson());
        $this->assertStringNotContainsString($version->original_path, $this->actingAs($owner)->get(route('account.photos.index'))->getContent());

        $response = $this->actingAs($owner)->get(route('account.photos.file', [$photo, 'thumbnail']));
        $response->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Disposition', 'inline; filename="photo.webp"');

        $this->assertContains($this->get('/storage/'.$version->original_path)->status(), [403, 404]);
        $disk->assertExists($version->original_path);
    }

    public function test_original_variant_is_never_served_even_to_the_owner(): void
    {
        [$owner, $photo] = $this->uploadPhoto();

        $this->actingAs($owner)
            ->get(route('account.photos.file', [$photo, 'original']))
            ->assertNotFound();
    }

    public function test_invalid_upload_is_rejected_before_processing(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->post(route('account.photos.store'), [
            'photo' => UploadedFile::fake()->create('fake.jpg', 10, 'image/jpeg'),
        ])->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('model_photos', 0);
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

        return [$owner, ModelPhoto::query()->with('latestVersion')->firstOrFail()];
    }
}
