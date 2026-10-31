<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ModelProfiles\Pages\ListModelProfiles;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class ModelListUsabilityTest extends TestCase
{
    public function test_tabs_filters_search_and_actions_preserve_actionable_scope(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $enrollment = User::factory()->withCompletedRegistration()->create(['profile_completion_status' => 'pending_review'])->modelProfile;
        $identity = User::factory()->withCompletedRegistration()->create()->modelProfile;
        $identity->forceFill(['identity_status' => 'pending', 'stage_name' => 'Modelo Buscable'])->save();
        $incomplete = User::factory()->withCompletedRegistration()->create()->modelProfile;
        $incomplete->forceFill(['identity_status' => 'incomplete', 'review_status' => 'pending'])->save();
        $published = User::factory()->withCompletedRegistration()->create()->modelProfile;
        $published->forceFill(['identity_status' => 'approved', 'review_status' => 'approved', 'is_published' => true])->save();
        $this->actingAs($admin);
        $list = Livewire::test(ListModelProfiles::class);
        $this->assertSame(['all', 'pending', 'published', 'unpublished'], array_keys($list->instance()->getTabs()));
        $this->assertSame(['stage_name', 'review_summary', 'is_published', 'payment_status'], array_keys($list->instance()->getTable()->getColumns()));
        $list->set('activeTab', 'pending')
            ->assertCanSeeTableRecords([$enrollment, $identity])
            ->assertCanNotSeeTableRecords([$incomplete, $published])
            ->assertTableActionVisible('view', $identity)
            ->assertTableActionVisible('approveIdentity', $identity)
            ->assertTableActionHidden('registerPayment', $identity);
        $list->set('activeTab', 'published')->assertCanSeeTableRecords([$published])->assertCanNotSeeTableRecords([$identity]);
        $list->set('activeTab', 'unpublished')->assertCanNotSeeTableRecords([$published]);
        $list->set('activeTab', 'all')->filterTable('moderation', 'pending_identity')
            ->assertCanSeeTableRecords([$identity])->assertCanNotSeeTableRecords([$enrollment, $incomplete]);
        $list->resetTableFilters()->searchTable('Modelo Buscable')
            ->assertCanSeeTableRecords([$identity])->assertCanNotSeeTableRecords([$enrollment, $published]);
    }
}
