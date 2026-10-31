<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\ModelProfiles\ModelProfileResource;
use App\Filament\Resources\ModelProfiles\Pages\ListModelProfiles;
use App\Filament\Widgets\PendingIdentities;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class PendingIdentitiesTest extends TestCase
{
    public function test_counter_and_destination_only_include_actionable_identities(): void
    {
        $admin = User::factory()->withCompletedRegistration()->create(['is_admin' => true]);
        $pending = User::factory()->withCompletedRegistration()->create()->modelProfile;
        $pending->forceFill(['identity_status' => 'pending'])->save();
        $excluded = collect();
        foreach (['incomplete', 'approved', 'rejected'] as $status) {
            $profile = User::factory()->withCompletedRegistration()->create()->modelProfile;
            $profile->forceFill(['identity_status' => $status])->save();
            $excluded->push($profile);
        }
        foreach (['profile_incomplete', 'pending_review'] as $status) {
            $profile = User::factory()->withCompletedRegistration()->create(['profile_completion_status' => $status])->modelProfile;
            $profile->forceFill(['identity_status' => 'pending'])->save();
            $excluded->push($profile);
        }
        $admin->modelProfile->forceFill(['identity_status' => 'pending'])->save();
        $excluded->push($admin->modelProfile);
        $this->actingAs($admin);

        Livewire::test(Dashboard::class)->assertSeeLivewire(PendingIdentities::class);
        $this->assertSame(1, PendingIdentities::query()->count());
        Livewire::test(PendingIdentities::class)
            ->assertSee('Identidades por verificar')
            ->assertSee(ModelProfileResource::getUrl('index', ['tab' => 'pending_identity']));
        Livewire::withQueryParams(['tab' => 'pending_identity'])->test(ListModelProfiles::class)
            ->assertSet('activeTab', 'pending_identity')
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords($excluded)
            ->assertTableActionVisible('approveIdentity', $pending)
            ->assertTableActionVisible('rejectIdentity', $pending);

        foreach (['approved', 'rejected'] as $status) {
            $pending->forceFill(['identity_status' => $status])->save();
            $this->assertSame(0, PendingIdentities::query()->count());
            Livewire::test(PendingIdentities::class)->assertSee('No hay identidades pendientes de verificación');
        }
    }
}
