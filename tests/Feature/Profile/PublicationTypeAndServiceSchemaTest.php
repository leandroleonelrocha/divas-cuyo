<?php

namespace Tests\Feature\Profile;

use App\Models\PublicationType;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\PublicationTypeSeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicationTypeAndServiceSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_catalogs_and_many_to_many_relationship_are_available(): void
    {
        $this->seed([PublicationTypeSeeder::class, ServiceSeeder::class]);

        $virtual = PublicationType::query()->where('slug', 'virtual')->firstOrFail();
        $encounters = PublicationType::query()->where('slug', 'encounters')->firstOrFail();
        $videoCall = Service::query()->where('slug', 'videollamada')->firstOrFail();
        $meeting = Service::query()->where('slug', 'encuentros')->firstOrFail();
        $user = User::factory()->create();
        $profile = $user->modelProfile;

        $profile->update(['publication_type_id' => $encounters->id]);
        $profile->services()->sync([$videoCall->id, $meeting->id]);

        $this->assertFalse($virtual->allows_in_person_services);
        $this->assertTrue($encounters->allows_in_person_services);
        $this->assertCount(2, $profile->fresh()->services);
        $this->assertSame('virtual', $videoCall->service_type);
        $this->assertSame('in_person', $meeting->service_type);
    }

    public function test_service_catalog_scope_excludes_inactive_entries(): void
    {
        $inactive = Service::factory()->create(['is_active' => false]);

        $this->assertFalse(Service::query()->active()->whereKey($inactive)->exists());
    }
}
