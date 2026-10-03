<?php

namespace Tests\Feature\PublicModels;

use App\Models\Locality;
use App\Models\Province;
use Illuminate\Support\Facades\Storage;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class WelcomePublicProfilesTest extends TestCase
{
    use PublicModelProfileFixtures;

    public function test_home_cards_only_list_profiles_that_pass_public_visibility(): void
    {
        $first = $this->publicProfile('mia');
        $firstVersion = $first->photos()->first()->currentVersion;
        $firstImage = Storage::disk('model_photos')->get($firstVersion->public_path);
        $second = $this->publicProfile('luna');
        $second->forceFill(['stage_name' => 'Luna'])->save();
        $secondVersion = $second->photos()->first()->currentVersion;
        $secondImage = Storage::disk('model_photos')->get($secondVersion->public_path);
        $hidden = $this->publicProfile('oculta');
        $hidden->forceFill(['stage_name' => 'No Publicada', 'is_published' => false])->save();
        Storage::disk('model_photos')->put($firstVersion->public_path, $firstImage);
        Storage::disk('model_photos')->put($secondVersion->public_path, $secondImage);

        $response = $this->get('/')->assertOk()
            ->assertSee('Mia')
            ->assertSee('Luna')
            ->assertDontSee('No Publicada')
            ->assertSee(route('public.models.show', ['slug' => 'mia']))
            ->assertSee(route('public.models.show', ['slug' => 'luna']))
            ->assertSee(route('public.models.photos.show', ['publicToken' => $first->photos()->first()->currentVersion->public_token]));

        $this->assertSame(2, preg_match_all('/class="profile-card"/', $response->getContent()));
    }

    public function test_home_exposes_real_location_options_and_location_keys_for_frontend_filtering(): void
    {
        $profile = $this->publicProfile('mia');
        $inactiveProvince = Province::factory()->create(['name' => 'Provincia Oculta', 'slug' => 'provincia-oculta', 'is_active' => false]);
        Locality::factory()->create([
            'province_id' => $inactiveProvince->id,
            'name' => 'Localidad Oculta',
            'slug' => 'localidad-oculta',
            'is_active' => true,
        ]);

        $response = $this->get('/')->assertOk();
        $profile->load(['province', 'locality']);

        $response->assertSee('id="province-filter"', false)
            ->assertSee('id="locality-filter"', false)
            ->assertSee('id="locality-filter" disabled', false)
            ->assertSee('data-province="'.$profile->province->slug.'"', false)
            ->assertSee('data-locality="'.$profile->locality->slug.'"', false)
            ->assertSee('value="'.$profile->province->slug.'"', false)
            ->assertSee('value="'.$profile->locality->slug.'"', false)
            ->assertDontSee('Provincia Oculta')
            ->assertDontSee('Localidad Oculta');
    }
}
