<?php

namespace Tests\Feature\Profile;

use App\Models\Locality;
use App\Models\Province;
use App\Models\User;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalogs_have_ids_and_initial_provinces_can_be_seeded(): void
    {
        $this->seed(ProvinceSeeder::class);

        $this->assertDatabaseHas('provinces', ['slug' => 'mendoza', 'is_active' => true]);
        $this->assertDatabaseHas('provinces', ['slug' => 'san-juan', 'is_active' => true]);
        $this->assertDatabaseHas('provinces', ['slug' => 'san-luis', 'is_active' => true]);
        $this->assertNotNull(Province::query()->where('slug', 'mendoza')->value('id'));
    }

    public function test_model_can_save_a_valid_province_locality_pair(): void
    {
        [$province, $locality] = $this->catalogPair();
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('account.profile.update'), [
            ...$this->validPayload(),
            'province_id' => $province->id,
            'locality_id' => $locality->id,
            'approximate_location_text' => 'Zona centro',
            'approximate_latitude' => '-32.8895',
            'approximate_longitude' => '-68.8458',
        ])->assertRedirect(route('account.profile.edit'));

        $profile = $user->modelProfile->fresh();
        $this->assertSame($province->id, $profile->province_id);
        $this->assertSame($locality->id, $profile->locality_id);
        $this->assertSame('Zona centro', $profile->approximate_location_text);
    }

    public function test_locality_from_another_province_is_rejected(): void
    {
        [$mendoza, $mendozaLocality] = $this->catalogPair('mendoza');
        [$sanJuan] = $this->catalogPair('san-juan');
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('account.profile.update'), [
            ...$this->validPayload(),
            'province_id' => $sanJuan->id,
            'locality_id' => $mendozaLocality->id,
        ])->assertSessionHasErrors('locality_id');

        $this->assertNull($user->modelProfile->fresh()->province_id);
        $this->assertNotSame($mendoza->id, $user->modelProfile->fresh()->province_id);
    }

    public function test_inactive_or_unknown_province_is_rejected(): void
    {
        [$province, $locality] = $this->catalogPair();
        $province->update(['is_active' => false]);
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('account.profile.update'), [
            ...$this->validPayload(),
            'province_id' => $province->id,
            'locality_id' => $locality->id,
        ])->assertSessionHasErrors('province_id');

        $this->actingAs($user)->patch(route('account.profile.update'), [
            ...$this->validPayload(),
            'province_id' => 999999,
        ])->assertSessionHasErrors('province_id');
    }

    /** @return array{0: Province, 1: Locality} */
    private function catalogPair(string $slug = 'mendoza'): array
    {
        $province = Province::factory()->create([
            'name' => str($slug)->headline()->toString(),
            'slug' => $slug,
        ]);
        $locality = $province->localities()->create([
            'name' => 'Ciudad',
            'slug' => 'ciudad',
        ]);

        return [$province, $locality];
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
