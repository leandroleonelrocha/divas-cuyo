<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ModelProfiles\Pages\ListModelProfiles;
use App\Filament\Resources\ModelProfiles\Pages\ViewModelProfile;
use App\Models\User;
use App\Services\ModelProfileModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class ModelProfileModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_profile_can_be_approved_and_records_reviewer(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $profile = User::factory()->create()->modelProfile;
        $profile->forceFill(['identity_status' => 'approved'])->save();

        app(ModelProfileModerationService::class)->approve($profile, $admin);
        $profile->refresh();

        $this->assertSame('approved', $profile->review_status);
        $this->assertFalse($profile->is_published);
        $this->assertTrue($profile->reviewed_at instanceof Carbon);
        $this->assertSame($admin->id, $profile->reviewed_by);
    }

    public function test_pending_profile_can_be_rejected_and_cannot_remain_published(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $profile = User::factory()->create()->modelProfile;

        app(ModelProfileModerationService::class)->reject($profile, $admin);
        $profile->refresh();

        $this->assertSame('rejected', $profile->review_status);
        $this->assertFalse($profile->is_published);
        $this->assertSame($admin->id, $profile->reviewed_by);
    }

    public function test_approved_profile_can_be_published_and_then_unpublished(): void
    {
        $profile = User::factory()->create()->modelProfile;
        $admin = User::factory()->create(['is_admin' => true]);
        $profile->forceFill(['identity_status' => 'approved'])->save();
        app(ModelProfileModerationService::class)->approve($profile, $admin);

        app(ModelProfileModerationService::class)->publish($profile);
        $this->assertTrue($profile->fresh()->is_published);

        app(ModelProfileModerationService::class)->unpublish($profile);
        $profile->refresh();

        $this->assertFalse($profile->is_published);
        $this->assertSame('approved', $profile->review_status);
    }

    public function test_invalid_publication_transitions_are_rejected(): void
    {
        $profile = User::factory()->create()->modelProfile;
        $service = app(ModelProfileModerationService::class);

        $this->expectException(LogicException::class);
        $service->publish($profile);

        $profile->refresh();
        $this->assertFalse($profile->is_published);
        $this->assertSame('pending', $profile->review_status);
    }

    public function test_moderation_actions_are_visible_only_for_valid_transitions(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $pending = User::factory()->create()->modelProfile;
        $pending->forceFill(['identity_status' => 'approved'])->save();

        Livewire::actingAs($admin)
            ->test(ListModelProfiles::class)
            ->assertTableActionVisible('approve', $pending)
            ->assertTableActionVisible('reject', $pending)
            ->assertTableActionHidden('publish', $pending)
            ->assertTableActionHidden('unpublish', $pending);
    }

    public function test_admin_can_execute_a_confirmed_approval_action_from_the_list(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $profile = User::factory()->create()->modelProfile;
        $profile->forceFill(['identity_status' => 'approved'])->save();

        Livewire::actingAs($admin)
            ->test(ListModelProfiles::class)
            ->callTableAction('approve', $profile);

        $this->assertSame('approved', $profile->fresh()->review_status);
        $this->assertSame($admin->id, $profile->fresh()->reviewed_by);
    }

    public function test_detail_exposes_state_actions_to_an_authorized_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $profile = User::factory()->create()->modelProfile;
        $profile->forceFill(['identity_status' => 'approved'])->save();

        Livewire::actingAs($admin)
            ->test(ViewModelProfile::class, ['record' => $profile->getKey()])
            ->assertActionVisible('approve')
            ->assertActionVisible('reject')
            ->assertActionHidden('publish')
            ->assertActionHidden('unpublish');
    }

    public function test_non_admin_cannot_invoke_moderation_actions(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $profile = User::factory()->create()->modelProfile;

        $this->actingAs($user)
            ->get('/admin/model-profiles/'.$profile->getKey())
            ->assertForbidden();
    }

    public function test_profile_approval_requires_validated_identity_and_preserves_profile_state_when_blocked(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        foreach (['incomplete', 'pending', 'rejected'] as $identityStatus) {
            $profile = User::factory()->create()->modelProfile;
            $profile->forceFill(['identity_status' => $identityStatus])->save();

            try {
                app(ModelProfileModerationService::class)->approve($profile, $admin);
                $this->fail("Identity status {$identityStatus} should block profile approval.");
            } catch (LogicException $exception) {
                $this->assertSame('No se puede aprobar el perfil hasta que la identidad esté validada.', $exception->getMessage());
            }

            $this->assertSame('pending', $profile->fresh()->review_status);
            $this->assertNull($profile->fresh()->reviewed_at);
            $this->assertNull($profile->fresh()->reviewed_by);
        }

        $allowed = User::factory()->create()->modelProfile;
        $allowed->forceFill(['identity_status' => 'approved'])->save();
        app(ModelProfileModerationService::class)->approve($allowed, $admin);

        $this->assertSame('approved', $allowed->fresh()->review_status);
    }

    public function test_profile_publication_requires_approved_identity_and_profile(): void
    {
        $service = app(ModelProfileModerationService::class);

        foreach (['incomplete', 'pending', 'rejected'] as $identityStatus) {
            $profile = User::factory()->create()->modelProfile;
            $profile->forceFill([
                'identity_status' => $identityStatus,
                'review_status' => 'approved',
            ])->save();

            try {
                $service->publish($profile);
                $this->fail("Identity status {$identityStatus} should block publication.");
            } catch (LogicException $exception) {
                $this->assertSame('No se puede publicar el perfil hasta que la identidad y el perfil estén aprobados.', $exception->getMessage());
            }

            $this->assertFalse($profile->fresh()->is_published);
        }

        foreach (['pending', 'rejected'] as $reviewStatus) {
            $profile = User::factory()->create()->modelProfile;
            $profile->forceFill([
                'identity_status' => 'approved',
                'review_status' => $reviewStatus,
            ])->save();

            try {
                $service->publish($profile);
                $this->fail("Review status {$reviewStatus} should block publication.");
            } catch (LogicException $exception) {
                $this->assertSame('Sólo se puede publicar un perfil aprobado y no publicado.', $exception->getMessage());
            }

            $this->assertFalse($profile->fresh()->is_published);
        }

        $allowed = User::factory()->create()->modelProfile;
        $allowed->forceFill([
            'identity_status' => 'approved',
            'review_status' => 'approved',
        ])->save();
        $service->publish($allowed);

        $this->assertTrue($allowed->fresh()->is_published);
    }

    public function test_profile_moderation_keeps_identity_independent(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $profile = User::factory()->create()->modelProfile;
        $profile->forceFill(['identity_status' => 'approved'])->save();

        app(ModelProfileModerationService::class)->approve($profile, $admin);
        $this->assertSame('approved', $profile->fresh()->identity_status);

        app(ModelProfileModerationService::class)->reject($profile, $admin);
        $this->assertSame('approved', $profile->fresh()->identity_status);

        app(ModelProfileModerationService::class)->approve($profile, $admin);
        app(ModelProfileModerationService::class)->publish($profile);
        app(ModelProfileModerationService::class)->unpublish($profile);

        $current = $profile->fresh();
        $this->assertSame('approved', $current->identity_status);
        $this->assertSame('approved', $current->review_status);
        $this->assertFalse($current->is_published);
    }
}
