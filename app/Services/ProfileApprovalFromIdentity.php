<?php

namespace App\Services;

use App\Models\ModelProfile;

class ProfileApprovalFromIdentity
{
    public function sync(ModelProfile $profile): void
    {
        if ($profile->identity_status !== 'approved'
            || $profile->review_status !== 'pending'
            || $profile->user?->profile_completion_status !== AccountEnrollmentState::ACTIVE
            || blank($profile->stage_name) || blank($profile->slug)
            || ! app(ProfileCompletionService::class)->isComplete($profile->user)) {
            return;
        }

        $profile->forceFill([
            'review_status' => 'approved',
            'reviewed_at' => $profile->identity_reviewed_at ?? now(),
            'reviewed_by' => $profile->identity_reviewed_by,
        ])->save();
    }
}
