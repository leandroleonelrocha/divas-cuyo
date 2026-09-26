<?php

namespace Tests\Feature\Account;

use App\Models\ModelPhoto;
use App\Models\ModelProfile;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModelPhotoReorderTest extends TestCase
{
    public function test_model_can_reorder_its_own_photos_with_dense_positions(): void
    {
        $user = User::factory()->create();
        $photos = collect(range(0, 2))->map(fn (int $position) => $this->createPhoto($user->modelProfile, $position));

        $this->actingAs($user)
            ->patch(route('account.photos.order'), [
                'photo_ids' => $photos->reverse()->pluck('id')->values()->all(),
            ])
            ->assertRedirect(route('account.photos.index'));

        $this->assertSame(
            $photos->reverse()->pluck('id')->values()->all(),
            ModelPhoto::query()->where('model_profile_id', $user->modelProfile->id)->orderBy('position')->pluck('id')->all(),
        );
        $this->assertSame([0, 1, 2], ModelPhoto::query()->where('model_profile_id', $user->modelProfile->id)->orderBy('position')->pluck('position')->all());
    }

    public function test_reorder_rejects_foreign_duplicate_and_incomplete_photo_sets(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $own = $this->createPhoto($user->modelProfile, 0);
        $foreign = $this->createPhoto($other->modelProfile, 0);

        $this->actingAs($user)->patch(route('account.photos.order'), [
            'model_profile_id' => $other->modelProfile->id,
            'photo_ids' => [$own->id, $foreign->id],
        ])->assertSessionHasErrors('photo_ids');

        $this->actingAs($user)->patch(route('account.photos.order'), [
            'photo_ids' => [$own->id, $own->id],
        ])->assertSessionHasErrors('photo_ids.1');

        $this->assertSame(0, $own->fresh()->position);
        $this->assertSame(0, $foreign->fresh()->position);
    }

    private function createPhoto(ModelProfile $profile, int $position): ModelPhoto
    {
        $photo = ModelPhoto::query()->create([
            'model_profile_id' => $profile->id,
            'position' => $position,
            'is_primary' => false,
        ]);
        $token = Str::uuid();
        $photo->versions()->create([
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
            'status' => 'pending',
        ]);

        return $photo->fresh();
    }
}
