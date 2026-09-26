<?php

namespace Tests\Feature\Profile;

use App\Models\PublicationType;
use App\Models\User;
use Database\Seeders\PublicationTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_can_save_available_status(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('account.profile.update'), [
            ...$this->validPayload(),
            'availability_status' => 'available',
        ])->assertRedirect(route('account.profile.edit'));

        $this->assertSame('available', $user->modelProfile->fresh()->availability_status);
    }

    public function test_model_can_change_availability_without_changing_publication_or_review_states(): void
    {
        $this->seed(PublicationTypeSeeder::class);
        $user = User::factory()->create();
        $profile = $user->modelProfile;
        $publicationType = PublicationType::query()->where('slug', 'virtual')->firstOrFail();
        $profile->forceFill([
            'publication_type_id' => $publicationType->id,
            'review_status' => 'approved',
            'identity_status' => 'approved',
            'is_published' => true,
        ])->save();

        $this->actingAs($user)->patch(route('account.profile.update'), [
            ...$this->validPayload(),
            'availability_status' => 'unavailable',
        ])->assertRedirect(route('account.profile.edit'));

        $profile->refresh();
        $this->assertSame('unavailable', $profile->availability_status);
        $this->assertTrue($profile->is_published);
        $this->assertSame('approved', $profile->review_status);
        $this->assertSame('approved', $profile->identity_status);
        $this->assertSame($publicationType->id, $profile->publication_type_id);
    }

    public function test_invalid_availability_status_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('account.profile.update'), [
            ...$this->validPayload(),
            'availability_status' => 'online',
        ])->assertSessionHasErrors('availability_status');

        $this->assertSame('available', $user->modelProfile->fresh()->availability_status);
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'real_first_name' => 'Mía',
            'real_last_name' => 'Privada',
            'birth_date' => '1990-05-20',
            'stage_name' => 'Mia',
            'public_age' => 31,
            'show_age' => true,
            'nationality' => 'Argentina',
        ];
    }
}
