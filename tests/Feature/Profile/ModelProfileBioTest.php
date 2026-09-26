<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use App\Services\ModelProfileBioModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelProfileBioTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_submission_is_pending_and_not_public(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('account.profile.bios.store'), [
            'content' => 'Una presentación inicial para revisión.',
        ])->assertRedirect(route('account.profile.edit'));

        $bio = $user->modelProfile->bios()->firstOrFail();
        $this->assertSame('pending', $bio->status);
        $this->assertNull($user->modelProfile->fresh()->current_bio_id);
        $this->assertDontSeeBioOnAccount($user, $bio->content);
    }

    public function test_new_version_does_not_replace_current_approved_bio(): void
    {
        $user = User::factory()->create();
        $approved = $user->modelProfile->bios()->create([
            'content' => 'Presentación aprobada visible.',
            'status' => 'approved',
        ]);
        $user->modelProfile->update(['current_bio_id' => $approved->id]);

        $this->actingAs($user)->post(route('account.profile.bios.store'), [
            'content' => 'Una nueva presentación pendiente.',
        ])->assertRedirect(route('account.profile.edit'));

        $profile = $user->modelProfile->fresh();
        $this->assertSame($approved->id, $profile->current_bio_id);
        $this->assertSame('pending', $profile->bios()->where('status', 'pending')->firstOrFail()->status);
        $this->get(route('account.dashboard'))->assertSee($approved->content)->assertDontSee('Una nueva presentación pendiente.');
    }

    public function test_rejected_bio_can_be_resent_without_overwriting_history(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $rejected = $user->modelProfile->bios()->create([
            'content' => 'Texto rechazado para corregir.',
            'status' => 'pending',
        ]);
        app(ModelProfileBioModerationService::class)->reject($rejected, $admin, 'Falta información de presentación.');

        $this->actingAs($user)->post(route('account.profile.bios.store'), [
            'content' => 'Texto corregido y enviado nuevamente.',
        ])->assertRedirect(route('account.profile.edit'));

        $this->assertSame('rejected', $rejected->fresh()->status);
        $this->assertDatabaseHas('model_profile_bios', [
            'model_profile_id' => $user->modelProfile->id,
            'content' => 'Texto corregido y enviado nuevamente.',
            'status' => 'pending',
        ]);
        $this->get(route('account.profile.edit'))
            ->assertSee('Falta información de presentación.')
            ->assertSee('Texto corregido y enviado nuevamente.');
    }

    public function test_model_cannot_submit_for_another_profile_and_bio_does_not_change_profile_states(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $profile = $user->modelProfile;
        $profile->forceFill([
            'identity_status' => 'approved',
            'review_status' => 'approved',
            'is_published' => true,
        ])->save();

        $this->actingAs($user)->post(route('account.profile.bios.store'), [
            'model_profile_id' => $other->modelProfile->id,
            'content' => 'Texto propio para moderación.',
        ])->assertRedirect(route('account.profile.edit'));

        $profile->refresh();
        $this->assertSame(1, $profile->bios()->count());
        $this->assertSame('approved', $profile->identity_status);
        $this->assertSame('approved', $profile->review_status);
        $this->assertTrue($profile->is_published);
        $this->assertCount(0, $other->modelProfile->bios);
    }

    public function test_invalid_bio_length_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('account.profile.bios.store'), [
            'content' => 'corto',
        ])->assertSessionHasErrors('content');
    }

    private function assertDontSeeBioOnAccount(User $user, string $content): void
    {
        $this->get(route('account.dashboard'))
            ->assertDontSee($content);
    }
}
