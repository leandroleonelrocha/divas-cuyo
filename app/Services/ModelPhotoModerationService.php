<?php

namespace App\Services;

use App\Enums\ModelNotificationType;
use App\Models\ModelPhoto;
use App\Models\User;

class ModelPhotoModerationService
{
    public function __construct(private readonly ModelPhotoService $photos) {}

    public function approve(ModelPhoto $photo, User $reviewer): ModelPhoto
    {
        $result = $this->photos->moderatePendingVersion($photo, $reviewer, 'approved');

        app(ModelNotificationService::class)->record($result->modelProfile, ModelNotificationType::PhotoApproved);

        return $result;
    }

    public function reject(ModelPhoto $photo, User $reviewer, string $reason): ModelPhoto
    {
        $result = $this->photos->moderatePendingVersion($photo, $reviewer, 'rejected', $reason);

        app(ModelNotificationService::class)->record($result->modelProfile, ModelNotificationType::PhotoRejected, $reason);

        return $result;
    }
}
