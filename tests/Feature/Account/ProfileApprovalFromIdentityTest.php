<?php

namespace Tests\Feature\Account;

use App\Models\User;
use App\Services\IdentityDocumentService;
use App\Services\ProfileApprovalFromIdentity;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileApprovalFromIdentityTest extends TestCase
{
    public function test_identity_approval_validates_complete_profile_without_publishing(): void
    {
        Storage::fake('identity_private');
        $admin = User::factory()->create(['is_admin' => true]);
        $profile = User::factory()->withCompletedRegistration()->create()->modelProfile;
        $profile->forceFill(['stage_name' => 'Mia', 'slug' => 'mia'])->save();
        $service = app(IdentityDocumentService::class);
        foreach (config('identity-documents.required_types') as $type) {
            $service->upload($profile, $type, UploadedFile::fake()->image('document.jpg'));
        }
        $service->submit($profile);
        $service->approveIdentity($profile, $admin);
        $this->assertSame('approved', $profile->fresh()->review_status);
        $this->assertFalse($profile->fresh()->is_published);
        $this->assertSame($admin->id, $profile->fresh()->reviewed_by);
    }

    public function test_incomplete_or_explicitly_rejected_profiles_are_not_automatically_approved(): void
    {
        $profile = User::factory()->withCompletedRegistration()->create()->modelProfile;
        $profile->forceFill(['identity_status' => 'approved', 'stage_name' => null])->save();
        app(ProfileApprovalFromIdentity::class)->sync($profile);
        $this->assertSame('pending', $profile->fresh()->review_status);
        $profile->forceFill(['stage_name' => 'Mia', 'slug' => 'mia', 'review_status' => 'rejected'])->save();
        app(ProfileApprovalFromIdentity::class)->sync($profile);
        $this->assertSame('rejected', $profile->fresh()->review_status);
    }
}
