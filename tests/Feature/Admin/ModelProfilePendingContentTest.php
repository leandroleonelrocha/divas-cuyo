<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ModelProfiles\ModelProfileResource;
use App\Filament\Resources\ModelProfiles\Pages\ListModelProfiles;
use App\Filament\Resources\ModelProfiles\RelationManagers\ModelPhotosRelationManager;
use App\Filament\Resources\ModelProfiles\RelationManagers\ModelProfileBiosRelationManager;
use App\Models\ModelProfile;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class ModelProfilePendingContentTest extends TestCase
{
    private function profile(): ModelProfile
    {
        $profile = User::factory()->withCompletedRegistration()->create()->modelProfile;
        $profile->update(['review_status' => 'approved', 'identity_status' => 'approved', 'is_published' => true]);

        return $profile;
    }

    private function photo(ModelProfile $profile, string $latestStatus = 'pending'): void
    {
        $photo = $profile->photos()->create(['position' => 0, 'is_primary' => false]);
        foreach ([1 => 'pending', 2 => $latestStatus] as $version => $status) {
            $photo->versions()->create([
                'version' => $version, 'status' => $status,
                'original_path' => 'original.jpg', 'processed_path' => 'processed.webp',
                'public_path' => 'public.webp', 'thumbnail_path' => 'thumbnail.webp',
                'mime_type' => 'image/webp', 'file_size' => 100,
                'width' => 100, 'height' => 100, 'processed_width' => 100, 'processed_height' => 100,
            ]);
        }
    }

    public function test_list_counts_latest_photos_and_pending_bios_and_links_to_their_review(): void
    {
        $admin = User::factory()->withCompletedRegistration()->create(['is_admin' => true]);
        $pending = $this->profile();
        $clear = $this->profile();
        $this->photo($pending);
        $this->photo($clear, 'rejected'); // An older pending version must not appear as actionable.
        $pending->bios()->create(['content' => 'Nuevo texto', 'status' => 'pending']);
        $clear->bios()->create(['content' => 'Texto aprobado', 'status' => 'approved']);
        $this->actingAs($admin);

        $record = ModelProfileResource::getEloquentQuery()->findOrFail($pending->id);
        $this->assertSame(1, $record->pending_photos_count);
        $this->assertSame(1, $record->pending_bios_count);
        $this->assertSame(0, ModelProfileResource::getEloquentQuery()->findOrFail($clear->id)->pending_photos_count);
        $this->assertSame('1', ModelProfileResource::getNavigationBadge());

        $list = Livewire::test(ListModelProfiles::class)
            ->assertCanSeeTableRecords([$pending, $clear])
            ->assertSee('Revisión')->assertSee('Pendiente')->assertSee('Ver detalle');
        $list->assertSee(ModelProfileResource::getUrl('view', ['record' => $pending]));
        $list->set('activeTab', 'pending_photos')->assertCanSeeTableRecords([$pending])->assertCanNotSeeTableRecords([$clear]);
        $list->set('activeTab', 'pending_bios')->assertCanSeeTableRecords([$pending])->assertCanNotSeeTableRecords([$clear]);
        $pending->bios()->update(['status' => 'approved']);
        $pending->photos->first()->latestVersion->update(['status' => 'approved']);
        $this->assertNull(ModelProfileResource::getNavigationBadge());
        Livewire::test(ListModelProfiles::class)->set('activeTab', 'pending_bios')->assertCanNotSeeTableRecords([$pending]);
    }
}
