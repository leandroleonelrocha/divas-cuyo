<?php

namespace App\Http\Controllers;

use App\Services\ModelNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountNotificationController extends Controller
{
    public function markAllRead(Request $request): JsonResponse
    {
        $profile = $request->user()?->modelProfile;

        if (! $profile) {
            return response()->json(['unread' => 0, 'updated' => 0]);
        }

        $updated = app(ModelNotificationService::class)->markAllRead($profile);

        return response()->json(['unread' => 0, 'updated' => $updated]);
    }
}
