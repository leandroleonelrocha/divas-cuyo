<?php

namespace Tests\Feature\PublicModels;

use App\Models\ModelProfile;
use App\Models\PublicationType;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class PublicModelServicesTest extends TestCase
{
    use PublicModelProfileFixtures;
    use RefreshDatabase;

    public function test_virtual_type_only_displays_associated_active_virtual_services(): void
    {
        $profile = $this->publicProfile();
        $type = $this->publicationType('virtual', 'Solo Virtual');
        $profile->update(['publication_type_id' => $type->id]);

        $virtualLater = $this->service('Virtual posterior', 'virtual', 20);
        $virtualEarlier = $this->service('Virtual anterior', 'virtual', 10);
        $inPerson = $this->service('Presencial inconsistente', 'in_person', 1);
        $inactiveVirtual = $this->service('Virtual inactivo', 'virtual', 0, false);
        $profile->services()->attach([$virtualLater->id, $virtualEarlier->id, $inPerson->id, $inactiveVirtual->id]);

        $response = $this->get('/modelos/mia')->assertOk()
            ->assertSee('Solo Virtual')
            ->assertSee('Servicios virtuales')
            ->assertSeeInOrder(['Virtual anterior', 'Virtual posterior'])
            ->assertDontSee('Servicios presenciales')
            ->assertDontSee('Presencial inconsistente')
            ->assertDontSee('Virtual inactivo');

        $response->assertViewHas('publicProfile', fn ($viewModel): bool => $viewModel->publicationTypeLabel === 'Solo Virtual'
            && $viewModel->serviceGroups === ['Servicios virtuales' => ['Virtual anterior', 'Virtual posterior']]
        );
        $this->assertNoServiceInternals($response->getContent(), $response->viewData('publicProfile'));
    }

    public function test_encounters_groups_active_services_in_sort_order_even_if_type_is_inactive(): void
    {
        $profile = $this->publicProfile();
        $type = $this->publicationType('encounters', 'Encuentros', false);
        $profile->update(['publication_type_id' => $type->id]);

        $virtualFirst = $this->service('Virtual primero', 'virtual', 10);
        $virtualSecond = $this->service('Virtual segundo', 'virtual', 10);
        $virtualLast = $this->service('Virtual último', 'virtual', 30);
        $inPerson = $this->service('Presencial primero', 'in_person', 5);
        $inactive = $this->service('Presencial inactivo', 'in_person', 0, false);
        $profile->services()->attach([$virtualFirst->id, $virtualSecond->id, $virtualLast->id, $inPerson->id, $inactive->id]);

        $response = $this->get('/modelos/mia')->assertOk()
            ->assertSee('Encuentros')
            ->assertSeeInOrder(['Servicios virtuales', 'Virtual primero', 'Virtual segundo', 'Virtual último'])
            ->assertSeeInOrder(['Servicios presenciales', 'Presencial primero'])
            ->assertDontSee('Presencial inactivo');

        $response->assertViewHas('publicProfile', fn ($viewModel): bool => $viewModel->publicationTypeLabel === 'Encuentros'
            && $viewModel->serviceGroups === [
                'Servicios virtuales' => ['Virtual primero', 'Virtual segundo', 'Virtual último'],
                'Servicios presenciales' => ['Presencial primero'],
            ]
        );
        $this->assertNoServiceInternals($response->getContent(), $response->viewData('publicProfile'));
    }

    public function test_missing_or_unknown_type_does_not_enable_service_groups(): void
    {
        $profile = $this->publicProfile();
        $virtual = $this->service('Servicio virtual', 'virtual', 1);
        $inPerson = $this->service('Servicio presencial', 'in_person', 2);
        $profile->services()->attach([$virtual->id, $inPerson->id]);

        $missingType = $this->get('/modelos/mia')->assertOk()
            ->assertDontSee('Solo Virtual')
            ->assertDontSee('Encuentros')
            ->assertDontSee('Servicios virtuales')
            ->assertDontSee('Servicios presenciales')
            ->assertDontSee('Servicio virtual')
            ->assertDontSee('Servicio presencial');
        $missingType->assertViewHas('publicProfile', fn ($viewModel): bool => $viewModel->publicationTypeLabel === null && $viewModel->serviceGroups === []
        );

        $unknownType = $this->publicationType('unknown-mode', 'Modalidad no clasificada');
        $profile->update(['publication_type_id' => $unknownType->id]);
        $unknown = $this->get('/modelos/mia')->assertOk()
            ->assertDontSee('Modalidad no clasificada')
            ->assertDontSee('Servicios virtuales')
            ->assertDontSee('Servicios presenciales')
            ->assertDontSee('Servicio virtual')
            ->assertDontSee('Servicio presencial');
        $unknown->assertViewHas('publicProfile', fn ($viewModel): bool => $viewModel->publicationTypeLabel === null && $viewModel->serviceGroups === []
        );
    }

    public function test_empty_service_groups_and_section_are_omitted(): void
    {
        $profile = $this->publicProfile();
        $type = $this->publicationType('encounters', 'Encuentros');
        $profile->update(['publication_type_id' => $type->id]);
        $inactive = $this->service('Servicio inactivo', 'virtual', 1, false);
        $profile->services()->attach($inactive->id);

        $response = $this->get('/modelos/mia')->assertOk()
            ->assertSee('Encuentros')
            ->assertDontSee('Servicios virtuales')
            ->assertDontSee('Servicios presenciales')
            ->assertDontSee('Servicios');

        $response->assertViewHas('publicProfile', fn ($viewModel): bool => $viewModel->publicationTypeLabel === 'Encuentros' && $viewModel->serviceGroups === []
        );
    }

    public function test_deactivated_service_disappears_on_the_next_request(): void
    {
        $profile = $this->publicProfile();
        $type = $this->publicationType('virtual', 'Solo Virtual');
        $profile->update(['publication_type_id' => $type->id]);
        $service = $this->service('Videollamada', 'virtual', 1);
        $profile->services()->attach($service->id);

        $this->get('/modelos/mia')->assertOk()->assertSee('Videollamada');

        $service->update(['is_active' => false]);
        $this->get('/modelos/mia')->assertOk()
            ->assertDontSee('Servicios')
            ->assertDontSee('Videollamada');
        $this->assertTrue($profile->fresh()->is_published);
    }

    private function publicationType(string $slug, string $name, bool $active = true): PublicationType
    {
        return PublicationType::factory()->create([
            'slug' => $slug,
            'name' => $name,
            'is_active' => $active,
        ]);
    }

    private function service(string $name, string $type, int $order, bool $active = true): Service
    {
        return Service::factory()->create([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'service_type' => $type,
            'sort_order' => $order,
            'is_active' => $active,
        ]);
    }

    private function assertNoServiceInternals(string $html, mixed $viewModel): void
    {
        foreach (['publication_type_id', 'service_type', 'model_profile_service', 'sort_order', 'pivot', 'publicationTypeHistory'] as $internal) {
            $this->assertStringNotContainsString($internal, $html);
        }

        $this->assertNotInstanceOf(ModelProfile::class, $viewModel);
        $publicFields = array_keys(get_object_vars($viewModel));
        foreach (['id', 'modelProfileId', 'publicationTypeId', 'serviceIds', 'history', 'pivot'] as $internal) {
            $this->assertNotContains($internal, $publicFields);
        }
    }
}
