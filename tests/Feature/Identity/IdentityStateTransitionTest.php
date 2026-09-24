<?php

namespace Tests\Feature\Identity;

use App\Models\ModelProfile;
use App\Models\User;
use App\Services\IdentityDocumentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

class IdentityStateTransitionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('identity_private');
    }

    public function test_only_the_documented_identity_transitions_are_allowed(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $service = app(IdentityDocumentService::class);

        $profile = User::factory()->create()->modelProfile;
        $this->addRequiredDocuments($profile, $service);
        $service->submit($profile);
        $service->approveIdentity($profile, $admin);
        $this->assertSame('approved', $profile->fresh()->identity_status);

        $rejected = User::factory()->create()->modelProfile;
        $this->addRequiredDocuments($rejected, $service);
        $service->submit($rejected);
        $service->rejectIdentity($rejected, $admin, 'Documento ilegible.');
        $this->assertSame('rejected', $rejected->fresh()->identity_status);
        $service->submit($rejected);
        $this->assertSame('pending', $rejected->fresh()->identity_status);
    }

    public function test_invalid_identity_transitions_are_rejected_server_side(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $service = app(IdentityDocumentService::class);

        foreach (['incomplete', 'approved', 'rejected'] as $status) {
            $profile = User::factory()->create()->modelProfile;
            $profile->forceFill(['identity_status' => $status])->save();

            try {
                $service->approveIdentity($profile, $admin);
                $this->fail("Identity status {$status} should not be approvable.");
            } catch (LogicException) {
                $this->assertSame($status, $profile->fresh()->identity_status);
            }
        }

        foreach (['incomplete', 'approved', 'rejected'] as $status) {
            $profile = User::factory()->create()->modelProfile;
            $profile->forceFill(['identity_status' => $status])->save();

            try {
                $service->rejectIdentity($profile, $admin, 'Motivo');
                $this->fail("Identity status {$status} should not be rejectable.");
            } catch (LogicException) {
                $this->assertSame($status, $profile->fresh()->identity_status);
            }
        }
    }

    public function test_resolution_rechecks_a_stale_profile_state_inside_the_transaction(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $service = app(IdentityDocumentService::class);
        $profile = User::factory()->create()->modelProfile;
        $this->addRequiredDocuments($profile, $service);
        $service->submit($profile);

        $staleProfile = $profile->fresh();
        $profile->forceFill(['identity_status' => 'rejected'])->save();

        try {
            $service->approveIdentity($staleProfile, $admin);
            $this->fail('A stale pending profile should not be approved.');
        } catch (LogicException) {
            $this->assertSame('rejected', $profile->fresh()->identity_status);
        }
    }

    private function addRequiredDocuments(ModelProfile $profile, IdentityDocumentService $service): void
    {
        foreach (['dni_front', 'dni_back', 'selfie'] as $type) {
            $service->upload($profile, $type, UploadedFile::fake()->image($type.'.jpg'));
        }
    }
}
