<?php

namespace Tests\Feature\Profile;

use App\Models\Province;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileLocationRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_location_preserves_existing_profile_states_and_photos(): void
    {
        $oldProvince = Province::factory()->create(['slug' => 'old-province']);
        $oldLocality = $oldProvince->localities()->create(['name' => 'Anterior', 'slug' => 'anterior']);
        $newProvince = Province::factory()->create(['slug' => 'new-province']);
        $newLocality = $newProvince->localities()->create(['name' => 'Nueva', 'slug' => 'nueva']);
        $user = User::factory()->create();
        $this->seed(ServiceSeeder::class);
        $profile = $user->modelProfile;
        $profile->forceFill([
            'province_id' => $oldProvince->id,
            'locality_id' => $oldLocality->id,
            'identity_status' => 'approved',
            'review_status' => 'approved',
            'is_published' => true,
        ])->save();
        $photo = $profile->photos()->create(['position' => 1, 'is_primary' => true]);
        $bio = $profile->bios()->create(['content' => 'Biografía pública aprobada.', 'status' => 'approved']);
        $profile->update(['current_bio_id' => $bio->id]);
        $service = Service::query()->where('service_type', 'virtual')->firstOrFail();
        $profile->services()->sync([$service->id]);

        $this->actingAs($user)->patch(route('account.profile.update'), [
            ...$this->validPayload(),
            'province_id' => $newProvince->id,
            'locality_id' => $newLocality->id,
        ])->assertRedirect(route('account.profile.edit'));

        $profile->refresh();
        $this->assertSame($newProvince->id, $profile->province_id);
        $this->assertSame($newLocality->id, $profile->locality_id);
        $this->assertSame('approved', $profile->identity_status);
        $this->assertSame('approved', $profile->review_status);
        $this->assertTrue($profile->is_published);
        $this->assertDatabaseHas('model_photos', [
            'id' => $photo->id,
            'model_profile_id' => $profile->id,
        ]);
        $this->assertSame($bio->id, $profile->fresh()->current_bio_id);
        $this->assertTrue($profile->services()->whereKey($service)->exists());
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'real_first_name' => 'Mía',
            'real_last_name' => 'Privada',
            'birth_date' => '1990-05-20',
            'stage_name' => 'Mia',
            'show_age' => true,
            'nationality' => 'Argentina',
            'availability_status' => 'available',
        ];
    }
}
