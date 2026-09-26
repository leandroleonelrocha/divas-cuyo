<?php

namespace Tests\Feature\Profile;

use App\Models\ModelProfilePublicationTypeHistory;
use App\Models\PublicationType;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\PublicationTypeSeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicationTypeChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_changes_type_and_records_history_without_touching_profile_states(): void
    {
        $this->seed(PublicationTypeSeeder::class);
        $this->seed(ServiceSeeder::class);
        $user = User::factory()->create();
        $virtual = PublicationType::query()->where('slug', 'virtual')->firstOrFail();
        $encounters = PublicationType::query()->where('slug', 'encounters')->firstOrFail();
        $profile = $user->modelProfile;
        $profile->forceFill([
            'publication_type_id' => $encounters->id,
            'identity_status' => 'approved',
            'review_status' => 'approved',
            'is_published' => true,
        ])->save();
        $virtualService = Service::query()->where('service_type', 'virtual')->firstOrFail();
        $inPersonService = Service::query()->where('service_type', 'in_person')->firstOrFail();
        $profile->services()->sync([$virtualService->id, $inPersonService->id]);

        $this->actingAs($user)->patch(route('account.profile.publication-type.update'), [
            'publication_type_id' => $virtual->id,
        ])->assertSessionHasErrors('confirm_in_person_removal');

        $this->assertSame($encounters->id, $profile->fresh()->publication_type_id);

        $this->actingAs($user)->patch(route('account.profile.publication-type.update'), [
            'publication_type_id' => $virtual->id,
            'reason' => 'La modelo amplió su modalidad.',
            'confirm_in_person_removal' => true,
        ])->assertRedirect(route('account.profile.edit'));

        $profile->refresh();
        $this->assertSame($virtual->id, $profile->publication_type_id);
        $this->assertSame('approved', $profile->identity_status);
        $this->assertSame('approved', $profile->review_status);
        $this->assertTrue($profile->is_published);
        $this->assertDatabaseHas('model_profile_publication_type_history', [
            'model_profile_id' => $profile->id,
            'from_publication_type_id' => $encounters->id,
            'to_publication_type_id' => $virtual->id,
            'changed_by_user_id' => $user->id,
            'source' => 'model',
            'reason' => 'La modelo amplió su modalidad.',
        ]);
        $this->assertTrue($profile->services()->whereKey($virtualService)->exists());
        $this->assertFalse($profile->services()->whereKey($inPersonService)->exists());
    }

    public function test_noop_does_not_create_redundant_history(): void
    {
        $this->seed(PublicationTypeSeeder::class);
        $user = User::factory()->create();
        $virtual = PublicationType::query()->where('slug', 'virtual')->firstOrFail();
        $user->modelProfile->update(['publication_type_id' => $virtual->id]);

        $this->actingAs($user)->patch(route('account.profile.publication-type.update'), [
            'publication_type_id' => $virtual->id,
        ])->assertRedirect(route('account.profile.edit'));

        $this->assertSame(0, ModelProfilePublicationTypeHistory::query()->count());
    }

    public function test_virtual_to_encounters_preserves_virtual_services_without_auto_assigning_in_person(): void
    {
        $this->seed([PublicationTypeSeeder::class, ServiceSeeder::class]);
        $user = User::factory()->create();
        $virtual = PublicationType::query()->where('slug', 'virtual')->firstOrFail();
        $encounters = PublicationType::query()->where('slug', 'encounters')->firstOrFail();
        $virtualService = Service::query()->where('service_type', 'virtual')->firstOrFail();
        $inPersonService = Service::query()->where('service_type', 'in_person')->firstOrFail();
        $profile = $user->modelProfile;
        $profile->update(['publication_type_id' => $virtual->id]);
        $profile->services()->sync([$virtualService->id]);

        $this->actingAs($user)->patch(route('account.profile.publication-type.update'), [
            'publication_type_id' => $encounters->id,
        ])->assertRedirect(route('account.profile.edit'));

        $profile->refresh();
        $this->assertSame($encounters->id, $profile->publication_type_id);
        $this->assertTrue($profile->services()->whereKey($virtualService)->exists());
        $this->assertFalse($profile->services()->whereKey($inPersonService)->exists());
    }

    public function test_inactive_type_is_rejected_and_current_type_is_preserved(): void
    {
        $this->seed(PublicationTypeSeeder::class);
        $user = User::factory()->create();
        $virtual = PublicationType::query()->where('slug', 'virtual')->firstOrFail();
        $inactive = PublicationType::factory()->create(['is_active' => false]);
        $user->modelProfile->update(['publication_type_id' => $virtual->id]);

        $this->actingAs($user)->patch(route('account.profile.publication-type.update'), [
            'publication_type_id' => $inactive->id,
        ])->assertSessionHasErrors('publication_type_id');

        $this->assertSame($virtual->id, $user->modelProfile->fresh()->publication_type_id);
    }

    public function test_generic_profile_update_cannot_change_publication_type(): void
    {
        $this->seed(PublicationTypeSeeder::class);
        $user = User::factory()->create();
        $virtual = PublicationType::query()->where('slug', 'virtual')->firstOrFail();
        $encounters = PublicationType::query()->where('slug', 'encounters')->firstOrFail();
        $user->modelProfile->update(['publication_type_id' => $virtual->id]);

        $this->actingAs($user)->patch(route('account.profile.update'), [
            ...$this->validPayload(),
            'publication_type_id' => $encounters->id,
        ])->assertRedirect(route('account.profile.edit'));

        $this->assertSame($virtual->id, $user->modelProfile->fresh()->publication_type_id);
        $this->assertSame(0, ModelProfilePublicationTypeHistory::query()->count());
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'real_first_name' => 'Mía',
            'real_last_name' => 'Privada',
            'birth_date' => '1990-05-20',
            'stage_name' => 'Mia',
            'show_age' => true,
            'nationality' => 'Argentina',
            'availability_status' => 'available',
        ];
    }
}
