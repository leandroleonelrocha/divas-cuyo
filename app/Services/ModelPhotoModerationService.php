<?php

namespace App\Services;

use App\Models\ModelPhoto;
use App\Models\User;

class ModelPhotoModerationService
{
    public function __construct(private readonly ModelPhotoService $photos) {}

    public function approve(ModelPhoto $photo, User $reviewer): ModelPhoto
    {
        return $this->photos->moderatePendingVersion($photo, $reviewer, 'approved');
    }

    public function reject(ModelPhoto $photo, User $reviewer, string $reason): ModelPhoto
    {
        return $this->photos->moderatePendingVersion($photo, $reviewer, 'rejected', $reason);
    }
}
