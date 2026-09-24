<?php

namespace Tests\Feature\Account;

use App\Models\ModelPhoto;
use App\Models\ModelProfile;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModelPhotoPrimaryTest extends TestCase
{
    public function test_only_an_approved_photo_can_become_primary_and_changing_it_unsets_the_previous_one(): void
    {
        $user = User::factory()->create();
        $first = $this->createPhoto($user->modelProfile, 0, 'approved', true);
        $second = $this->createPhoto($user->modelProfile, 1, 'approved');

        $this->actingAs($user)
            ->post(route('account.photos.primary', $second))
            ->assertRedirect(route('account.photos.index'));

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);
        $this->assertSame(1, ModelPhoto::query()->where('model_profile_id', $user->modelProfile->id)->where('is_primary', true)->count());
    }

    public function test_pending_and_rejected_photos_cannot_become_primary(): void
    {
        $user = User::factory()->create();
        $pending = $this->createPhoto($user->modelProfile, 0, 'pending');
        $rejected = $this->createPhoto($user->modelProfile, 1, 'rejected');

        foreach ([$pending, $rejected] as $photo) {
            $this->actingAs($user)
                ->post(route('account.photos.primary', $photo))
                ->assertSessionHasErrors('photo');
        }

        $this->assertSame(0, ModelPhoto::query()->where('model_profile_id', $user->modelProfile->id)->where('is_primary', true)->count());
    }

    public function test_model_cannot_mark_another_models_photo_as_primary(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreign = $this->createPhoto($other->modelProfile, 0, 'approved');

        $this->actingAs($user)
            ->post(route('account.photos.primary', $foreign))
            ->assertForbidden();
    }

    private function createPhoto(ModelProfile $profile, int $position, string $status, bool $primary = false): ModelPhoto
    {
        $photo = ModelPhoto::query()->create([
            'model_profile_id' => $profile->id,
            'position' => $position,
            'is_primary' => $primary,
        ]);
        $token = Str::uuid();
        $version = $photo->versions()->create([
            'version' => 1,
            'original_path' => "model-profiles/test/{$token}/original.jpg",
            'processed_path' => "model-profiles/test/{$token}/processed.webp",
            'public_path' => "model-profiles/test/{$token}/public.webp",
            'thumbnail_path' => "model-profiles/test/{$token}/thumbnail.webp",
            'mime_type' => 'image/jpeg',
            'file_size' => 100,
            'width' => 800,
            'height' => 800,
            'processed_width' => 800,
            'processed_height' => 800,
            'status' => $status,
        ]);

        if ($status === 'approved') {
            $photo->update(['current_version_id' => $version->id]);
        }

        return $photo->fresh();
    }
}
