<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\ModelProfiles\ModelProfileResource;
use App\Filament\Resources\ModelProfiles\Pages\ListModelProfiles;
use App\Filament\Widgets\PendingContent;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class PendingContentTest extends TestCase
{
    public function test_dashboard_counts_models_once_and_links_to_filtered_list(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $pending = User::factory()->withCompletedRegistration()->create()->modelProfile;
        $pending->forceFill(['identity_status' => 'approved', 'review_status' => 'approved'])->save();
        $pending->bios()->create(['content' => 'Texto', 'status' => 'pending']);
        $other = User::factory()->withCompletedRegistration()->create()->modelProfile;
        $other->bios()->create(['content' => 'Texto', 'status' => 'pending']);
        $this->actingAs($admin);
        $this->assertSame(1, PendingContent::query()->count());
        Livewire::test(Dashboard::class)->assertSeeLivewire(PendingContent::class);
        Livewire::test(PendingContent::class)
            ->assertSee('Contenido por revisar')
            ->assertSee(ModelProfileResource::getUrl('index', ['tab' => 'pending_content']));
        Livewire::withQueryParams(['tab' => 'pending_content'])->test(ListModelProfiles::class)
            ->assertCanSeeTableRecords([$pending])->assertCanNotSeeTableRecords([$other]);
        $pending->bios()->update(['status' => 'approved']);
        $this->assertSame(0, PendingContent::query()->count());
        Livewire::test(PendingContent::class)->assertSee('No hay contenido pendiente de revisión');
    }
}
