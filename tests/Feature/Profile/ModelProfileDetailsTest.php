<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelProfileDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_can_update_its_private_and_public_profile_details(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('account.profile.update'), [
            'real_first_name' => 'Mía',
            'real_last_name' => 'Privada',
            'birth_date' => '1990-05-20',
            'real_height_cm' => 170,
            'real_weight_kg' => 58.5,
            'real_measurements' => '90-60-90',
            'private_phone' => '+54 9 261 555 5555',
            'stage_name' => 'Mia',
            'public_age' => 35,
            'show_age' => true,
            'nationality' => 'Argentina',
        ]);

        $response->assertRedirect(route('account.profile.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('model_profile_private_details', [
            'model_profile_id' => $user->modelProfile->id,
            'real_first_name' => 'Mía',
            'real_last_name' => 'Privada',
            'private_phone' => '+54 9 261 555 5555',
        ]);
        $this->assertSame('1990-05-20', $user->modelProfile->privateDetails->fresh()->birth_date->format('Y-m-d'));
        $this->assertDatabaseHas('model_profiles', [
            'id' => $user->modelProfile->id,
            'stage_name' => 'Mia',
            'public_age' => 35,
            'show_age' => true,
            'nationality' => 'Argentina',
        ]);
    }

    public function test_profile_update_ignores_manipulated_ownership_fields(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($owner)->patch(route('account.profile.update'), [
            'model_profile_id' => $other->modelProfile->id,
            'user_id' => $other->id,
            'stage_name' => 'Sólo propio',
            'real_first_name' => 'Propia',
            'real_last_name' => 'Cuenta',
            'birth_date' => '1990-05-20',
            'public_age' => 35,
            'show_age' => false,
            'nationality' => 'Argentina',
        ])->assertRedirect(route('account.profile.edit'));

        $this->assertDatabaseHas('model_profiles', [
            'id' => $owner->modelProfile->id,
            'stage_name' => 'Sólo propio',
        ]);
        $this->assertDatabaseMissing('model_profiles', [
            'id' => $other->modelProfile->id,
            'stage_name' => 'Sólo propio',
        ]);
    }

    public function test_invalid_profile_details_are_rejected_without_partial_changes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('account.profile.update'), [
            'real_first_name' => '',
            'real_last_name' => '',
            'birth_date' => now()->addDay()->toDateString(),
            'stage_name' => '',
            'public_age' => 100,
            'show_age' => true,
            'nationality' => '',
        ])->assertSessionHasErrors([
            'real_first_name',
            'real_last_name',
            'birth_date',
            'stage_name',
            'public_age',
            'nationality',
        ]);

        $this->assertDatabaseMissing('model_profile_private_details', [
            'model_profile_id' => $user->modelProfile->id,
        ]);
    }
}
