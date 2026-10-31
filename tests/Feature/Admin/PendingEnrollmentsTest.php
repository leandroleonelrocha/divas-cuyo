<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\ModelProfiles\ModelProfileResource;
use App\Filament\Resources\ModelProfiles\Pages\ListModelProfiles;
use App\Filament\Widgets\PendingEnrollments;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class PendingEnrollmentsTest extends TestCase
{
    public function test_dashboard_links_to_pending_enrollments_and_updates_after_approval(): void
    {
        $admin = User::factory()->withCompletedRegistration()->create(['is_admin' => true, 'profile_completion_status' => 'pending_review']);
        $pending = User::factory()->withCompletedRegistration()->create(['profile_completion_status' => 'pending_review']);
        $active = User::factory()->withCompletedRegistration()->create();
        $incomplete = User::factory()->create(['profile_completion_status' => 'profile_incomplete']);
        $this->actingAs($admin);

        Livewire::test(Dashboard::class)->assertSeeLivewire(PendingEnrollments::class);
        $this->assertSame(1, PendingEnrollments::query()->count());
        Livewire::test(PendingEnrollments::class)
            ->assertSee('Inscripciones por habilitar')
            ->assertSee('Revisar inscripciones y habilitar acceso al panel')
            ->assertSee(ModelProfileResource::getUrl('index', ['tab' => 'pending_enrollment']));
        Livewire::withQueryParams(['tab' => 'pending_enrollment'])->test(ListModelProfiles::class)
            ->assertSet('activeTab', 'pending_enrollment')
            ->assertCanSeeTableRecords([$pending->modelProfile])
            ->assertCanNotSeeTableRecords([$active->modelProfile, $incomplete->modelProfile, $admin->modelProfile])
            ->callTableAction('finishEnrollmentReview', $pending->modelProfile)
            ->assertHasNoErrors()
            ->assertCanNotSeeTableRecords([$pending->modelProfile]);

        $this->assertSame(0, PendingEnrollments::query()->count());
        Livewire::test(PendingEnrollments::class)->assertSee('No hay inscripciones pendientes');
    }
}
