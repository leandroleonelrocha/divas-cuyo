<?php

namespace App\Services;

use App\Enums\ModelNotificationType;
use App\Models\ModelNotification;
use App\Models\ModelProfile;
use App\Notifications\ModelReviewNotification;
use Illuminate\Database\Eloquent\Collection;

class ModelNotificationService
{
    public function record(ModelProfile $profile, ModelNotificationType $type, ?string $detail = null): ModelNotification
    {
        $notification = ModelNotification::query()->create([
            'model_profile_id' => $profile->getKey(),
            'type' => $type,
            'detail' => $detail,
        ]);

        $profile->user?->notify(new ModelReviewNotification($type, $detail));

        return $notification;
    }

    public function latest(ModelProfile $profile, int $limit = 12): Collection
    {
        return ModelNotification::query()
            ->where('model_profile_id', $profile->getKey())
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function unreadCount(ModelProfile $profile): int
    {
        return ModelNotification::query()
            ->where('model_profile_id', $profile->getKey())
            ->unread()
            ->count();
    }

    public function markAllRead(ModelProfile $profile): int
    {
        return ModelNotification::query()
            ->where('model_profile_id', $profile->getKey())
            ->unread()
            ->update(['read_at' => now()]);
    }
}
