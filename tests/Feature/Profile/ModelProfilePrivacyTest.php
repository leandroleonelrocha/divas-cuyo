<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelProfilePrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_can_only_edit_the_profile_resolved_from_its_session(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($owner)->get(route('account.profile.edit'))
            ->assertOk()
            ->assertSee('Información privada')
            ->assertSee('Esta información es privada');

        $this->actingAs($owner)->get('/account/profile/'.$other->modelProfile->id)
            ->assertNotFound();
    }

    public function test_private_details_are_not_in_the_default_model_profile_serialization(): void
    {
        $user = User::factory()->create();
        $user->modelProfile->privateDetails()->create([
            'real_first_name' => 'Nombre Real',
            'real_last_name' => 'Privado',
            'birth_date' => '1990-05-20',
        ]);

        $serialized = $user->modelProfile->fresh()->toArray();

        $this->assertArrayNotHasKey('private_details', $serialized);
        $this->assertArrayNotHasKey('real_first_name', $serialized);
        $this->assertArrayNotHasKey('birth_date', $serialized);
    }
}
