<?php

namespace Tests\Feature\Profile;

use App\Models\PublicationType;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\PublicationTypeSeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_virtual_profile_accepts_virtual_service(): void
    {
        [$user, $virtual, $inPerson] = $this->profileWithCatalog('virtual');
        $virtualService = Service::query()->where('service_type', 'virtual')->firstOrFail();

        $this->actingAs($user)->patch(route('account.profile.services.update'), [
            'service_ids' => [$virtualService->id],
        ])->assertRedirect(route('account.profile.edit'));

        $this->assertTrue($user->modelProfile->services()->whereKey($virtualService)->exists());
        $this->assertSame('virtual', $virtual->slug);
        $this->assertSame('in_person', $inPerson->service_type);
    }

    public function test_virtual_profile_rejects_manipulated_in_person_service(): void
    {
        [$user] = $this->profileWithCatalog('virtual');
        $inPersonService = Service::query()->where('service_type', 'in_person')->firstOrFail();

        $this->actingAs($user)->patch(route('account.profile.services.update'), [
            'service_ids' => [$inPersonService->id],
        ])->assertSessionHasErrors('service_ids.0');

        $this->assertDatabaseMissing('model_profile_service', [
            'model_profile_id' => $user->modelProfile->id,
            'service_id' => $inPersonService->id,
        ]);
    }

    public function test_encounters_profile_accepts_virtual_and_in_person_services(): void
    {
        [$user] = $this->profileWithCatalog('encounters');
        $services = Service::query()->whereIn('service_type', ['virtual', 'in_person'])->take(2)->get();

        $this->actingAs($user)->patch(route('account.profile.services.update'), [
            'service_ids' => $services->modelKeys(),
        ])->assertRedirect(route('account.profile.edit'));

        $this->assertCount(2, $user->modelProfile->fresh()->services);
    }

    public function test_inactive_service_is_rejected(): void
    {
        [$user] = $this->profileWithCatalog('encounters');
        $inactive = Service::factory()->create(['service_type' => 'virtual', 'is_active' => false]);

        $this->actingAs($user)->patch(route('account.profile.services.update'), [
            'service_ids' => [$inactive->id],
        ])->assertSessionHasErrors('service_ids.0');
    }

    public function test_model_can_only_update_services_for_its_own_profile(): void
    {
        [$user] = $this->profileWithCatalog('virtual');
        $other = User::factory()->create();
        $virtualService = Service::query()->where('service_type', 'virtual')->firstOrFail();

        $this->actingAs($user)->patch(route('account.profile.services.update'), [
            'model_profile_id' => $other->modelProfile->id,
            'service_ids' => [$virtualService->id],
        ])->assertRedirect(route('account.profile.edit'));

        $this->assertTrue($user->modelProfile->services()->whereKey($virtualService)->exists());
        $this->assertCount(0, $other->modelProfile->services);
    }

    /** @return array{0: User, 1: PublicationType, 2: Service} */
    private function profileWithCatalog(string $typeSlug): array
    {
        $this->seed([PublicationTypeSeeder::class, ServiceSeeder::class]);
        $type = PublicationType::query()->where('slug', $typeSlug)->firstOrFail();
        $inPerson = Service::query()->where('service_type', 'in_person')->firstOrFail();
        $user = User::factory()->create();
        $user->modelProfile->update(['publication_type_id' => $type->id]);

        return [$user, $type, $inPerson];
    }
}
