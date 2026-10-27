<?php

namespace Tests\Feature\PublicModels;

use App\Models\PublicationType;
use App\Models\Service;
use App\Services\PublicModelHomepageProfiles;
use Tests\Support\AcceptsLegalDisclaimer;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class HomeCategoryFiltersTest extends TestCase
{
    use AcceptsLegalDisclaimer;
    use PublicModelProfileFixtures;

    public function test_home_exposes_categories_from_gender_and_active_services(): void
    {
        $profile = $this->publicProfile('mia');
        $profile->forceFill(['gender' => 'trans'])->save();
        $massage = Service::factory()->create(['slug' => 'masaje-relajante', 'is_active' => true]);
        $profile->services()->sync([$massage->id]);

        $this->assertSame(['escort', 'masajistas', 'trans'], app(PublicModelHomepageProfiles::class)->cards()[0]['categories']);
        $this->get('/')->assertOk()->assertSee('data-categories="escort masajistas trans"', false);

        $massage->update(['is_active' => false]);
        $this->assertSame(['escort', 'trans'], app(PublicModelHomepageProfiles::class)->cards()[0]['categories']);

        $massage->update(['is_active' => true]);
        $virtual = PublicationType::query()->firstOrCreate(
            ['slug' => 'virtual'],
            ['name' => 'Solo Virtual', 'allows_in_person_services' => false, 'is_active' => true]
        );
        $profile->forceFill(['publication_type_id' => $virtual->id])->save();
        $this->assertSame(['virtual', 'trans'], app(PublicModelHomepageProfiles::class)->cards()[0]['categories']);
    }
}
