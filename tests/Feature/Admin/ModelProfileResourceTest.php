<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ModelProfiles\ModelProfileResource;
use App\Filament\Resources\ModelProfiles\Pages\EditModelProfile;
use App\Filament\Resources\ModelProfiles\Pages\ListModelProfiles;
use App\Filament\Resources\ModelProfiles\Pages\ViewModelProfile;
use App\Filament\Resources\ModelProfiles\RelationManagers\ModelPhotosRelationManager;
use App\Filament\Resources\ModelProfiles\RelationManagers\ModelProfileBiosRelationManager;
use App\Filament\Resources\ModelProfiles\RelationManagers\ModelProfilePhysicalRevisionsRelationManager;
use App\Filament\Resources\ModelProfiles\RelationManagers\ModelProfileServicesRelationManager;
use App\Filament\Resources\ModelProfiles\RelationManagers\PublicationTypeHistoryRelationManager;
use App\Models\ModelProfile;
use App\Models\User;
use App\Services\ModelProfileModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModelProfileResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_separates_profile_sections_and_registers_all_administrative_relations(): void
    {
        $relationManagers = ModelProfileResource::getRelations();

        $this->assertContains(ModelPhotosRelationManager::class, $relationManagers);
        $this->assertContains(ModelProfilePhysicalRevisionsRelationManager::class, $relationManagers);
        $this->assertContains(ModelProfileServicesRelationManager::class, $relationManagers);
        $this->assertContains(ModelProfileBiosRelationManager::class, $relationManagers);
        $this->assertContains(PublicationTypeHistoryRelationManager::class, $relationManagers);

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(ModelProfileResource::getUrl('view', ['record' => ModelProfile::query()->firstOrFail()]))
            ->assertOk()
            ->assertSee('Datos privados administrativos')
            ->assertSee('Información pública')
            ->assertSee('Tipo y servicios')
            ->assertSee('Biografía pública');
    }

    public function test_admin_can_see_the_model_profiles_list_and_use_its_columns(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $profile = ModelProfile::query()->firstOrFail();

        Livewire::actingAs($admin)
            ->test(ListModelProfiles::class)
            ->assertCanSeeTableRecords([$profile])
            ->assertTableColumnExists('name')
            ->assertTableColumnExists('user.email')
            ->assertTableColumnExists('whatsapp')
            ->assertTableColumnExists('location')
            ->assertTableColumnExists('user.email_verified_at')
            ->assertTableColumnExists('review_status')
            ->assertTableColumnExists('is_published')
            ->assertTableColumnExists('created_at');
    }

    public function test_admin_root_shows_the_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()->assertSee('Resumen');
    }

    public function test_admin_can_open_a_model_profile_detail(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $profile = $admin->modelProfile;

        $this->actingAs($admin)
            ->get(ModelProfileResource::getUrl('view', ['record' => $profile]))
            ->assertOk()
            ->assertSee('Nombre público')
            ->assertSee($profile->name)
            ->assertSee($admin->email)
            ->assertSee($profile->whatsapp)
            ->assertSee($profile->location)
            ->assertSee('Pendiente')
            ->assertSee('No publicado');

        Livewire::actingAs($admin)
            ->test(ViewModelProfile::class, ['record' => $profile->getKey()])
            ->assertSee($profile->name)
            ->assertSee($admin->email)
            ->assertSee('Verificado');
    }

    public function test_detail_distinguishes_verification_review_and_publication_states(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $unverified = User::factory()->unverified()->create();
        $unverified->modelProfile()->update([
            'review_status' => 'approved',
            'is_published' => false,
        ]);
        $rejected = User::factory()->create();
        $rejected->modelProfile()->update([
            'review_status' => 'rejected',
            'is_published' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(ViewModelProfile::class, ['record' => $unverified->modelProfile->getKey()])
            ->assertSee('No verificado')
            ->assertSee('Aprobado')
            ->assertSee('No publicado');

        Livewire::actingAs($admin)
            ->test(ViewModelProfile::class, ['record' => $rejected->modelProfile->getKey()])
            ->assertSee('Rechazado')
            ->assertSee('No publicado');
    }

    public function test_regular_user_cannot_open_a_model_profile_detail(): void
    {
        $owner = User::factory()->create();
        $regularUser = User::factory()->create(['is_admin' => false]);

        $this->actingAs($regularUser)
            ->get(ModelProfileResource::getUrl('view', ['record' => $owner->modelProfile]))
            ->assertForbidden();
    }

    public function test_admin_can_search_by_profile_and_account_fields(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $matchingUser = User::factory()->create([
            'name' => 'Lucia Mendoza',
            'email' => 'lucia@example.com',
            'whatsapp' => '+54 9 261 123 4567',
            'location' => 'San Rafael',
        ]);
        $otherUser = User::factory()->create([
            'name' => 'Otra Modelo',
            'email' => 'otra@example.com',
            'whatsapp' => '+54 9 261 765 4321',
            'location' => 'Mendoza',
        ]);

        $component = Livewire::actingAs($admin)->test(ListModelProfiles::class);

        foreach (['Lucia Mendoza', 'lucia@example.com', '+54 9 261 123 4567', 'San Rafael'] as $search) {
            $component
                ->searchTable($search)
                ->assertCanSeeTableRecords([$matchingUser->modelProfile])
                ->assertCanNotSeeTableRecords([$otherUser->modelProfile]);
        }
    }

    public function test_admin_can_filter_by_verification_review_and_publication(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $verified = User::factory()->create(['email_verified_at' => now()]);
        $unverified = User::factory()->unverified()->create();
        $approved = $verified->modelProfile()->update(['review_status' => 'approved', 'is_published' => true]);

        $component = Livewire::actingAs($admin)->test(ListModelProfiles::class);

        $component
            ->filterTable('email_verified', true)
            ->assertCanSeeTableRecords([$verified->modelProfile])
            ->assertCanNotSeeTableRecords([$unverified->modelProfile]);

        $component
            ->removeTableFilter('email_verified')
            ->filterTable('review_status', 'approved')
            ->assertCanSeeTableRecords([$verified->modelProfile])
            ->assertCanNotSeeTableRecords([$unverified->modelProfile]);

        $component
            ->removeTableFilter('review_status')
            ->filterTable('is_published', true)
            ->assertCanSeeTableRecords([$verified->modelProfile])
            ->assertCanNotSeeTableRecords([$unverified->modelProfile]);
    }

    public function test_regular_users_cannot_access_the_model_profiles_list(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin/model-profiles')
            ->assertForbidden();
    }

    public function test_admin_can_edit_the_allowed_profile_fields(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $profile = $admin->modelProfile;

        Livewire::actingAs($admin)
            ->test(EditModelProfile::class, ['record' => $profile->getKey()])
            ->fillForm([
                'name' => 'Nombre actualizado',
                'whatsapp' => '+54 9 261 999 9999',
                'location' => 'Godoy Cruz',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('model_profiles', [
            'id' => $profile->getKey(),
            'name' => 'Nombre actualizado',
            'whatsapp' => '+54 9 261 999 9999',
            'location' => 'Godoy Cruz',
        ]);
    }

    public function test_invalid_edit_keeps_previous_values_and_preserves_states(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $profile = $admin->modelProfile;
        $profile->forceFill(['identity_status' => 'approved'])->save();
        app(ModelProfileModerationService::class)->approve($profile, $admin);
        app(ModelProfileModerationService::class)->publish($profile);
        $profile->refresh();

        $original = [
            ...$profile->only(['name', 'whatsapp', 'location', 'review_status', 'is_published', 'reviewed_by']),
            'reviewed_at' => $profile->reviewed_at?->toDateTimeString(),
        ];
        $originalEmail = $admin->email;
        $originalVerifiedAt = $admin->email_verified_at;

        Livewire::actingAs($admin)
            ->test(EditModelProfile::class, ['record' => $profile->getKey()])
            ->fillForm([
                'name' => '',
                'whatsapp' => str_repeat('1', 51),
                'location' => str_repeat('L', 256),
            ])
            ->call('save')
            ->assertHasFormErrors(['name', 'whatsapp', 'location']);

        $profile->refresh();
        $admin->refresh();

        $current = [
            ...$profile->only(['name', 'whatsapp', 'location', 'review_status', 'is_published', 'reviewed_by']),
            'reviewed_at' => $profile->reviewed_at?->toDateTimeString(),
        ];

        $this->assertSame($original, $current);
        $this->assertSame($originalEmail, $admin->email);
        $this->assertSame($originalVerifiedAt?->toDateTimeString(), $admin->email_verified_at?->toDateTimeString());
    }

    public function test_edit_form_cannot_change_account_or_moderation_fields(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $profile = $admin->modelProfile;
        $profile->forceFill(['identity_status' => 'approved'])->save();
        app(ModelProfileModerationService::class)->approve($profile, $admin);
        $profile->refresh();

        $originalPassword = $admin->password;
        $originalEmail = $admin->email;
        $originalIsAdmin = $admin->is_admin;
        $originalVerifiedAt = $admin->email_verified_at;
        $originalStatus = $profile->review_status;
        $originalPublished = $profile->is_published;
        $originalReviewedAt = $profile->reviewed_at;
        $originalReviewedBy = $profile->reviewed_by;

        Livewire::actingAs($admin)
            ->test(EditModelProfile::class, ['record' => $profile->getKey()])
            ->fillForm([
                'name' => 'Edición permitida',
                'whatsapp' => $profile->whatsapp,
                'location' => $profile->location,
                'email' => 'intruso@example.com',
                'password' => 'new-password',
                'email_verified_at' => null,
                'is_admin' => false,
                'review_status' => 'rejected',
                'is_published' => true,
                'reviewed_at' => null,
                'reviewed_by' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $profile->refresh();
        $admin->refresh();

        $this->assertSame('Edición permitida', $profile->name);
        $this->assertSame($originalStatus, $profile->review_status);
        $this->assertSame($originalPublished, $profile->is_published);
        $this->assertSame($originalReviewedAt?->toDateTimeString(), $profile->reviewed_at?->toDateTimeString());
        $this->assertSame($originalReviewedBy, $profile->reviewed_by);
        $this->assertSame($originalEmail, $admin->email);
        $this->assertSame($originalPassword, $admin->password);
        $this->assertSame($originalIsAdmin, $admin->is_admin);
        $this->assertSame($originalVerifiedAt?->toDateTimeString(), $admin->email_verified_at?->toDateTimeString());
    }

    public function test_regular_user_cannot_access_model_profile_edit(): void
    {
        $owner = User::factory()->create();
        $regularUser = User::factory()->create(['is_admin' => false]);

        $this->actingAs($regularUser)
            ->get(ModelProfileResource::getUrl('edit', ['record' => $owner->modelProfile]))
            ->assertForbidden();
    }
}
