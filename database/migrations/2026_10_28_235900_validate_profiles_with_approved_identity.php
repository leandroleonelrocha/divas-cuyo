<?php

use App\Models\ModelProfile;
use App\Services\ProfileApprovalFromIdentity;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ModelProfile::query()->where('identity_status', 'approved')->where('review_status', 'pending')
            ->chunkById(100, function ($profiles): void {
                foreach ($profiles as $profile) {
                    app(ProfileApprovalFromIdentity::class)->sync($profile);
                }
            });
    }

    public function down(): void
    {
        // Do not revoke approvals that may already have been used to publish.
    }
};
