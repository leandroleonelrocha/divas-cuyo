<?php

namespace App\Http\View\Composers;

use App\Services\ModelNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccountNotificationsComposer
{
    public function compose(View $view): void
    {
        $profile = Auth::user()?->modelProfile;

        $view->with([
            'notifications' => $profile
                ? app(ModelNotificationService::class)->latest($profile)
                : collect(),
            'unreadNotifications' => $profile
                ? app(ModelNotificationService::class)->unreadCount($profile)
                : 0,
        ]);
    }
}
