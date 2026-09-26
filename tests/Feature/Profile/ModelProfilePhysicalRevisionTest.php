<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use App\Services\ModelProfilePhysicalModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class ModelProfilePhysicalRevisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_submits_a_pending_physical_revision_without_changing_approved_values(): void
    {
        $user = User::factory()->create();
        $user->modelProfile->forceFill([
            'height_cm' => 165,
            'weight_kg' => 55,
            'measurements' => '88-60-90',
            'eye_color' => 'Marrón',
            'nationality' => 'Argentina',
        ])->save();

        $response = $this->actingAs($user)->post(route('account.profile.physical-revisions.store'), [
            'height_cm' => 170,
            'weight_kg' => 58,
            'measurements' => '90-60-90',
            'eye_color' => 'Verdes',
            'hair_color' => 'Castaño',
            'skin_color' => 'Clara',
            'body_type' => 'Atlética',
            'nationality' => 'Argentina',
        ]);

        $response->assertRedirect(route('account.profile.edit'))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('model_profile_physical_revisions', [
            'model_profile_id' => $user->modelProfile->id,
            'status' => 'pending',
            'height_cm' => 170,
            'eye_color' => 'Verdes',
        ]);
        $this->assertSame(165, $user->modelProfile->fresh()->height_cm);
    }

    public function test_admin_can_approve_a_physical_revision_and_promote_its_snapshot(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $revision = $user->modelProfile->physicalRevisions()->create([
            'height_cm' => 172,
            'weight_kg' => 60,
            'measurements' => '92-62-92',
            'eye_color' => 'Verdes',
            'nationality' => 'Argentina',
            'status' => 'pending',
            'submitted_by' => $user->id,
        ]);

        $this->actingAs($admin)->post(route('admin.model-profile-physical-revisions.approve', $revision))
            ->assertRedirect();

        $this->assertSame('approved', $revision->fresh()->status);
        $this->assertSame(172, $user->modelProfile->fresh()->height_cm);
        $this->assertSame($admin->id, $revision->fresh()->reviewed_by);
    }

    public function test_admin_rejection_requires_reason_and_keeps_approved_values(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $user->modelProfile->forceFill(['height_cm' => 165])->save();
        $revision = $user->modelProfile->physicalRevisions()->create([
            'height_cm' => 172,
            'weight_kg' => 60,
            'measurements' => '92-62-92',
            'eye_color' => 'Verdes',
            'nationality' => 'Argentina',
            'status' => 'pending',
            'submitted_by' => $user->id,
        ]);

        $this->actingAs($admin)->post(route('admin.model-profile-physical-revisions.reject', $revision), [
            'reason' => 'La información no coincide con la documentación.',
        ])->assertRedirect();

        $this->assertSame('rejected', $revision->fresh()->status);
        $this->assertSame('La información no coincide con la documentación.', $revision->fresh()->rejection_reason);
        $this->assertSame(165, $user->modelProfile->fresh()->height_cm);
    }

    public function test_physical_revision_service_rejects_a_second_pending_revision(): void
    {
        $user = User::factory()->create();
        $service = app(ModelProfilePhysicalModerationService::class);
        $data = [
            'height_cm' => 170,
            'weight_kg' => 58,
            'measurements' => '90-60-90',
            'eye_color' => 'Verdes',
            'nationality' => 'Argentina',
        ];

        $service->submit($user->modelProfile, $user, $data);

        $this->expectException(LogicException::class);
        $service->submit($user->modelProfile, $user, $data);
    }
}
