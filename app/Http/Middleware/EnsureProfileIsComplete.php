<?php

namespace App\Http\Middleware;

use App\Services\ProfileCompletionService;
use App\Services\AccountEnrollmentState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsComplete
{
    public function handle(Request $request, Closure $next, string $mode = 'panel'): Response
    {
        $user = $request->user()?->refresh();
        if ($user && ! $user->isVerifiedAdmin()) {
            $destination = null;
            $message = 'Completá tu perfil antes de continuar.';
            if ($user->profile_completion_status === AccountEnrollmentState::PENDING) {
                $destination = 'account.pending-review';
                $message = 'Tu perfil está en revisión. Esperá la aprobación para acceder al panel.';
            } elseif ($mode !== 'onboarding' && ($user->profile_completion_status !== AccountEnrollmentState::ACTIVE
                || ! app(ProfileCompletionService::class)->isComplete($user))) {
                $destination = 'account.incomplete-profile';
            }

            if ($destination) {
                return $request->expectsJson()
                    ? response()->json(['message' => $message, 'redirect_url' => route($destination)], 403)
                    : to_route($destination);
            }
        }

        return $next($request);
    }
}
