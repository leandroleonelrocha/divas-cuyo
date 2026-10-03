<?php

namespace Tests\Feature\PublicModels;

use App\Models\Locality;
use App\Models\Province;
use App\ViewModels\PublicModelProfileViewModel;
use Illuminate\Support\Facades\DB;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class PublicModelResponseBoundaryTest extends TestCase
{
    use PublicModelProfileFixtures;

    public function test_only_allowlisted_data_reaches_view_and_private_relations_are_not_queried(): void
    {
        $profile = $this->publicProfile();
        $profile->privateDetails()->create(['real_first_name' => 'SECRET_FIRST', 'real_last_name' => 'SECRET_LAST', 'birth_date' => '1981-11-23', 'private_phone' => 'SECRET_PHONE']);
        $profile->forceFill(['name' => 'SECRET_LEGACY', 'whatsapp' => 'SECRET_CONTACT', 'location' => 'SECRET_ADDRESS', 'public_age' => 37, 'show_age' => false, 'approximate_latitude' => -32.1234567, 'approximate_longitude' => -68.1234567])->save();
        DB::enableQueryLog();
        $response = $this->get('/modelos/mia')->assertOk();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $response->assertViewHas('publicProfile', fn ($data) => $data instanceof PublicModelProfileViewModel);
        $data = $response->viewData('publicProfile');
        $this->assertSame(['stageName', 'canonicalUrl', 'verifiedLabel', 'availabilityLabel', 'location', 'primaryPhoto', 'photos', 'characteristics', 'bio', 'publicationTypeLabel', 'serviceGroups', 'title', 'description'], array_keys(get_object_vars($data)));
        foreach (['SECRET_FIRST', 'SECRET_LAST', '1981-11-23', 'SECRET_PHONE', 'SECRET_LEGACY', 'SECRET_CONTACT', 'SECRET_ADDRESS', $profile->user->email, 'user_id', 'model_profile_id', 'reviewed_by', 'rejection_reason', 'storage_path', 'identity_status', 'review_status', '-32.1234567', '-68.1234567'] as $secret) {
            $response->assertDontSee($secret, false);
            $this->assertStringNotContainsString($secret, json_encode($data));
        }
        foreach ($queries as $query) {
            foreach (['model_profile_private_details', 'model_documents', 'physical_revisions', 'publication_type_history', 'real_first_name', 'birth_date', 'approximate_latitude'] as $private) {
                $this->assertStringNotContainsString($private, $query['query']);
            }
        }
        $this->assertArrayNotHasKey('profile', $response->original->getData());
        $this->assertArrayNotHasKey('user', $response->original->getData());
    }

    public function test_non_public_and_unknown_and_numeric_routes_have_identical_generic_errors(): void
    {
        $profile = $this->publicProfile();
        $profile->update(['is_published' => false]);
        $expected = $this->get('/modelos/missing')->assertNotFound()->getContent();
        foreach (['mia', (string) $profile->id, 'MIA', 'mia--two', str_repeat('a', 161)] as $slug) {
            $response = $this->get('/modelos/'.$slug)->assertNotFound()->assertHeaderMissing('Location');
            $this->assertSame($expected, $response->getContent());
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        }
    }

    public function test_visible_response_is_not_cacheable_and_name_is_escaped(): void
    {
        $profile = $this->publicProfile();
        $profile->update(['stage_name' => '<script>unsafe()</script>']);
        $response = $this->get('/modelos/mia')->assertOk()->assertSee('<script>unsafe()</script>')->assertDontSee('<script>unsafe()</script>', false);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response->assertHeaderMissing('ETag');
    }

    public function test_location_omits_incompatible_locality_and_missing_values(): void
    {
        $profile = $this->publicProfile();
        $province = Province::create(['slug' => 'san-juan', 'name' => 'San Juan']);
        $locality = Locality::create(['province_id' => $province->id, 'slug' => 'other', 'name' => 'INCOMPATIBLE_LOCALITY']);
        $profile->update(['locality_id' => $locality->id, 'approximate_location_text' => null]);
        $this->get('/modelos/mia')->assertOk()->assertSee('Mendoza')->assertDontSee('INCOMPATIBLE_LOCALITY');
        $profile->update(['province_id' => null, 'locality_id' => null]);
        $this->get('/modelos/mia')->assertOk()->assertDontSee('Ubicación');
    }
}
