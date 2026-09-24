<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ModelProfiles\Pages\ViewModelProfile;
use App\Models\ModelProfile;
use App\Models\User;
use App\Services\IdentityDocumentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class IdentityDocumentReviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('identity_private');
    }

    public function test_verified_admin_can_see_and_download_private_documents_from_the_profile_detail(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $model = User::factory()->create();
        $profile = $this->makePendingProfile($model);
        $document = $profile->documents()->where('type', 'dni_front')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(ViewModelProfile::class, ['record' => $profile->getKey()])
            ->assertSee('Verificación de identidad')
            ->assertSee('Estado de identidad')
            ->assertSee('DNI frente')
            ->assertSee('DNI dorso')
            ->assertSee('Selfie')
            ->assertSee('Pendiente')
            ->assertActionVisible('approveIdentity')
            ->assertActionVisible('rejectIdentity')
            ->assertDontSee($document->storage_path);

        $this->actingAs($admin)
            ->get(route('identity.documents.download', [$model, $document]))
            ->assertOk();

        $this->get('/storage/'.$document->storage_path)->assertForbidden();
    }

    public function test_unverified_admin_and_regular_user_cannot_access_identity_review(): void
    {
        $model = User::factory()->create();
        $profile = $this->makePendingProfile($model);
        $regular = User::factory()->create();
        $unverifiedAdmin = User::factory()->unverified()->create(['is_admin' => true]);

        $this->actingAs($regular)
            ->get('/admin/model-profiles/'.$profile->getKey())
            ->assertForbidden();
        $this->actingAs($unverifiedAdmin)
            ->get('/admin/model-profiles/'.$profile->getKey())
            ->assertForbidden();
    }

    public function test_admin_can_approve_pending_identity_and_audit_is_recorded_without_changing_other_states(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $model = User::factory()->create();
        $profile = $this->makePendingProfile($model);
        $originalEmailVerifiedAt = $model->email_verified_at?->toDateTimeString();

        Livewire::actingAs($admin)
            ->test(ViewModelProfile::class, ['record' => $profile->getKey()])
            ->callAction('approveIdentity');

        $profile->refresh();
        $this->assertSame('approved', $profile->identity_status);
        $this->assertInstanceOf(Carbon::class, $profile->identity_reviewed_at);
        $this->assertSame($admin->id, $profile->identity_reviewed_by);
        $this->assertNull($profile->identity_rejection_reason);
        $this->assertSame('pending', $profile->review_status);
        $this->assertFalse($profile->is_published);
        $this->assertSame($originalEmailVerifiedAt, $model->fresh()->email_verified_at?->toDateTimeString());
    }

    public function test_admin_must_provide_a_reason_to_reject_and_rejection_is_audited(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $model = User::factory()->create();
        $profile = $this->makePendingProfile($model);

        Livewire::actingAs($admin)
            ->test(ViewModelProfile::class, ['record' => $profile->getKey()])
            ->callAction('rejectIdentity', ['reason' => ''])
            ->assertHasFormErrors(['reason']);

        Livewire::actingAs($admin)
            ->test(ViewModelProfile::class, ['record' => $profile->getKey()])
            ->callAction('rejectIdentity', ['reason' => 'El DNI no se lee correctamente.']);

        $profile->refresh();
        $this->assertSame('rejected', $profile->identity_status);
        $this->assertSame('El DNI no se lee correctamente.', $profile->identity_rejection_reason);
        $this->assertInstanceOf(Carbon::class, $profile->identity_reviewed_at);
        $this->assertSame($admin->id, $profile->identity_reviewed_by);
        $this->assertSame('pending', $profile->review_status);
        $this->assertFalse($profile->is_published);
    }

    public function test_missing_documents_and_non_pending_states_block_identity_resolution(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $model = User::factory()->create();
        $profile = $model->modelProfile;
        $service = app(IdentityDocumentService::class);

        foreach (['dni_front', 'dni_back'] as $type) {
            $service->upload($profile, $type, UploadedFile::fake()->image($type.'.jpg'));
        }
        $profile->forceFill(['identity_status' => 'pending'])->save();

        $this->expectException(LogicException::class);
        $service->approveIdentity($profile, $admin);
    }

    public function test_approved_and_rejected_identities_do_not_show_review_actions(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        foreach (['approved', 'rejected'] as $status) {
            $model = User::factory()->create();
            $profile = $this->makePendingProfile($model);
            $profile->forceFill(['identity_status' => $status])->save();

            Livewire::actingAs($admin)
                ->test(ViewModelProfile::class, ['record' => $profile->getKey()])
                ->assertActionHidden('approveIdentity')
                ->assertActionHidden('rejectIdentity');
        }
    }

    private function makePendingProfile(User $model): ModelProfile
    {
        $profile = $model->modelProfile;
        $service = app(IdentityDocumentService::class);

        foreach (['dni_front', 'dni_back', 'selfie'] as $type) {
            $service->upload($profile, $type, UploadedFile::fake()->image($type.'.jpg'));
        }

        $profile->forceFill(['identity_status' => 'pending'])->save();

        return $profile->fresh(['user', 'documents']);
    }
}
