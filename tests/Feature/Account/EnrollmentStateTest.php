<?php

namespace Tests\Feature\Account;

use App\Filament\Resources\ModelProfiles\Pages\ListModelProfiles;
use App\Models\PublicationType;
use App\Models\User;
use App\Services\AccountEnrollmentState;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnrollmentStateTest extends TestCase
{
    public function test_admin_can_force_an_existing_complete_account_back_to_the_form(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->withCompletedRegistration()->create();
        $profile = $owner->modelProfile;
        Livewire::actingAs($admin)->test(ListModelProfiles::class)
            ->callTableAction('requireProfileCompletion', $profile)->assertHasNoErrors();
        $this->assertSame('profile_incomplete', $owner->fresh()->profile_completion_status);
        $this->actingAs($owner)->get(route('account.dashboard'))->assertRedirect(route('account.incomplete-profile'));
        $this->get(route('account.incomplete-profile'))->assertOk();
        $this->patchJson(route('account.profile.services.update'), [])->assertForbidden();
        $this->assertSame($profile->review_status, $profile->fresh()->review_status);
        $this->assertSame($profile->is_published, $profile->fresh()->is_published);
    }

    public function test_submission_moves_forced_account_to_review_and_admin_can_finish_it(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->withCompletedRegistration()->create();
        $profile = $owner->modelProfile;
        app(AccountEnrollmentState::class)->requireCompletion($owner, $admin);
        $type = PublicationType::factory()->create(['slug' => 'virtual']);
        $this->actingAs($owner)->post(route('account.complete-profile'), [
            'name' => $profile->name, 'whatsapp' => $profile->whatsapp,
            'province_id' => $profile->province_id, 'publication_type_id' => $type->id,
            'terms_accepted' => true, 'privacy_accepted' => true,
            'profile_completion_status' => 'active',
        ])->assertRedirect(route('account.pending-review'));
        $this->assertSame('pending_review', $owner->fresh()->profile_completion_status);
        $this->get(route('account.dashboard'))->assertRedirect(route('account.pending-review'));
        $this->get(route('account.pending-review'))->assertOk()->assertSee('Tu perfil está en revisión');
        Livewire::actingAs($admin)->test(ListModelProfiles::class)
            ->callTableAction('finishEnrollmentReview', $profile)->assertHasNoErrors();
        $this->assertSame('active', $owner->fresh()->profile_completion_status);
    }

    public function test_invalid_submission_does_not_clear_the_forced_state(): void
    {
        $owner = User::factory()->withCompletedRegistration()->create();
        $owner->forceFill(['profile_completion_status' => 'profile_incomplete'])->save();
        $this->actingAs($owner)->post(route('account.complete-profile'), [])->assertSessionHasErrors('name');
        $this->assertSame('profile_incomplete', $owner->fresh()->profile_completion_status);
    }

    public function test_non_admin_cannot_change_account_enrollment_state(): void
    {
        $owner = User::factory()->withCompletedRegistration()->create();
        $this->expectException(HttpException::class);
        app(AccountEnrollmentState::class)->requireCompletion($owner, $owner);
    }

    public function test_unverified_admin_cannot_change_account_enrollment_state(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->unverified()->create(['is_admin' => true]);
        $this->expectException(HttpException::class);
        app(AccountEnrollmentState::class)->requireCompletion($owner, $admin);
    }
}
