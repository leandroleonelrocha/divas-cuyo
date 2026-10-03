<?php

namespace Tests\Feature\PublicModels;

use App\Models\Locality;
use App\Models\Province;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class PublicModelAgeLocationTest extends TestCase
{
    use PublicModelProfileFixtures;

    public static function ages(): array
    {
        return [[true, 37, '37 años'], [false, 37, null], [true, null, null], [false, null, null]];
    }

    #[DataProvider('ages')]
    public function test_age_uses_only_public_value_when_allowed(bool $show, ?int $age, ?string $expected): void
    {
        $profile = $this->publicProfile();
        $profile->privateDetails()->create(['real_first_name' => 'PRIVATE_FIRST', 'real_last_name' => 'PRIVATE_LAST', 'birth_date' => '1971-02-03']);
        $profile->update(['show_age' => $show, 'public_age' => $age]);
        $response = $this->get('/modelos/mia')->assertOk()->assertDontSee('1971-02-03');
        $data = $response->viewData('publicProfile');
        $this->assertSame($expected, $data->characteristics['Edad'] ?? null);
        if ($expected !== null) {
            $response->assertSee($expected);
        } else {
            $response->assertDontSee('<dt>Edad</dt>', false)->assertDontSee('37 años');
            $this->assertArrayNotHasKey('Edad', $data->characteristics);
        }
        $this->assertArrayNotHasKey('public_age', (array) $data);
        $this->assertArrayNotHasKey('show_age', (array) $data);
        $this->assertStringNotContainsString('birth_date', serialize($data));
    }

    public function test_location_is_optional_compatible_escaped_text_without_coordinates_or_legacy_fallback(): void
    {
        $profile = $this->publicProfile();
        $profile->forceFill(['location' => 'DOMICILIO_PRIVADO_123', 'approximate_latitude' => -32.1234567, 'approximate_longitude' => -68.1234567])->save();
        $this->get('/modelos/mia')->assertSee('Mendoza')->assertSee('Ciudad de Mendoza')->assertSee('Zona centro');
        $other = Province::create(['slug' => 'san-juan', 'name' => 'San Juan']);
        $locality = Locality::create(['province_id' => $other->id, 'slug' => 'ajena', 'name' => 'LOCALIDAD_INCOMPATIBLE']);
        $profile->update(['locality_id' => $locality->id, 'approximate_location_text' => '<script>zona()</script>']);
        $response = $this->get('/modelos/mia')->assertOk()->assertSee('Mendoza')->assertDontSee('LOCALIDAD_INCOMPATIBLE')
            ->assertSee('<script>zona()</script>')->assertDontSee('<script>', false);
        foreach (['DOMICILIO_PRIVADO_123', '-32.1234567', '-68.1234567', 'approximate_latitude', 'approximate_longitude', '<iframe', 'maps.google', 'geolocation'] as $secret) {
            $response->assertDontSee($secret, false);
            $this->assertStringNotContainsString($secret, serialize($response->viewData('publicProfile')));
        }
        $profile->update(['province_id' => null, 'locality_id' => null, 'approximate_location_text' => '   ']);
        $response = $this->get('/modelos/mia')->assertOk()->assertDontSee('Ubicación');
        $this->assertSame([], $response->viewData('publicProfile')->location);
    }
}
