<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\ModelProfileBioModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ModelProfileBioModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_approves_pending_bio_and_promotes_it_as_current(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $old = $user->modelProfile->bios()->create(['content' => 'Bio anterior aprobada.', 'status' => 'approved']);
        $user->modelProfile->update(['current_bio_id' => $old->id]);
        $pending = $user->modelProfile->bios()->create(['content' => 'Bio nueva pendiente.', 'status' => 'pending']);

        $approved = app(ModelProfileBioModerationService::class)->approve($pending, $admin);

        $this->assertSame('approved', $approved->status);
        $this->assertSame($admin->id, $approved->reviewed_by);
        $this->assertNotNull($approved->reviewed_at);
        $this->assertSame($pending->id, $user->modelProfile->fresh()->current_bio_id);
        $this->assertSame('approved', $old->fresh()->status);
    }

    public function test_rejection_requires_reason_and_preserves_previous_current_bio(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $old = $user->modelProfile->bios()->create(['content' => 'Bio actual pública.', 'status' => 'approved']);
        $user->modelProfile->update(['current_bio_id' => $old->id]);
        $pending = $user->modelProfile->bios()->create(['content' => 'Bio a rechazar.', 'status' => 'pending']);

        try {
            app(ModelProfileBioModerationService::class)->reject($pending, $admin, '');
        } catch (ValidationException) {
            // Expected: rejection requires an explicit reason.
        }
        $this->assertSame('pending', $pending->fresh()->status);

        $rejected = app(ModelProfileBioModerationService::class)->reject($pending->fresh(), $admin, 'No cumple las pautas.');
        $this->assertSame('rejected', $rejected->status);
        $this->assertSame('No cumple las pautas.', $rejected->rejection_reason);
        $this->assertSame($old->id, $user->modelProfile->fresh()->current_bio_id);
    }

    public function test_model_cannot_approve_or_reject_its_own_bio(): void
    {
        $user = User::factory()->create();
        $bio = $user->modelProfile->bios()->create(['content' => 'Bio pendiente de moderación.', 'status' => 'pending']);

        $this->assertFalse(Gate::forUser($user)->allows('approve', $bio));
        $this->assertFalse(Gate::forUser($user)->allows('reject', $bio));
        $this->expectException(ValidationException::class);
        app(ModelProfileBioModerationService::class)->approve($bio, $user);
    }

    public function test_admin_view_keeps_reviewer_and_rejection_data_available_only_to_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $bio = $user->modelProfile->bios()->create(['content' => 'Bio para revisar.', 'status' => 'pending']);
        app(ModelProfileBioModerationService::class)->reject($bio, $admin, 'Motivo interno.');

        $this->assertTrue(Gate::forUser($admin)->allows('view', $bio->fresh()));
        $this->assertTrue(Gate::forUser($user)->allows('view', $bio->fresh()));
        $this->get(route('account.dashboard'))->assertDontSee('Motivo interno.');
    }
}
