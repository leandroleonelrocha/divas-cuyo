<?php

namespace App\Services;

class ModelPhotoDomainRules
{
    public function maxPhotos(): int
    {
        return (int) config('model-photos.max_photos');
    }

    public function statuses(): array
    {
        return ['pending', 'approved', 'rejected'];
    }

    public function canUpload(): bool
    {
        return true;
    }
}
