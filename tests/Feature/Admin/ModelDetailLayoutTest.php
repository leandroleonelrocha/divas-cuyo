<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ModelProfiles\Pages\ViewModelProfile;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class ModelDetailLayoutTest extends TestCase
{
    public function test_identity_actions_are_in_identity_tab_and_general_keeps_private_fields(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $profile = User::factory()->withCompletedRegistration()->create()->modelProfile;
        $profile->forceFill(['identity_status' => 'pending'])->save();
        $this->actingAs($admin);
        $page = Livewire::test(ViewModelProfile::class, ['record' => $profile->id])
            ->assertSee('General')->assertSee('Identidad')->assertSee('Contenido')
            ->assertSee('Pagos')->assertSee('Historial')
            ->assertSee('Datos privados administrativos')
            ->assertActionHidden('approveIdentity');
        $page->set('activeRelationManager', 'identity')
            ->assertActionVisible('approveIdentity')->assertActionVisible('rejectIdentity')
            ->assertActionHidden('registerPayment')
            ->assertSee('DNI frente')->assertSee('DNI dorso')->assertSee('Selfie');
    }

    public function test_grouped_detail_shows_actionable_tasks_and_contextual_actions(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $profile = User::factory()->withCompletedRegistration()->create()->modelProfile;
        $profile->forceFill(['identity_status' => 'approved', 'review_status' => 'approved'])->save();
        $profile->bios()->create(['content' => 'Revisar texto', 'status' => 'pending']);
        $this->actingAs($admin);
        $page = Livewire::test(ViewModelProfile::class, ['record' => $profile->id])
            ->assertSee('Requiere tu atención')->assertSee('Biografías')
            ->assertActionHidden('registerPayment');
        $this->assertCount(3, $page->instance()->getRelationManagers());
        $page->set('activeRelationManager', '0')->assertSee('Revisar texto')
            ->assertSee('review-ModelProfileBiosRelationManager');
        $page->set('activeRelationManager', '4')->assertActionVisible('registerPayment');
        $page->set('activeRelationManager', '6')->assertSee('Últimas moderaciones registradas');
        $profile->bios()->update(['status' => 'approved']);
        Livewire::test(ViewModelProfile::class, ['record' => $profile->id])
            ->assertDontSee('Requiere tu atención')->assertSee('No hay tareas de revisión pendientes.');
    }
}
