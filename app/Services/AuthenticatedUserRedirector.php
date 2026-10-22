<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

class AuthenticatedUserRedirector
{
    public function redirect(User $user): RedirectResponse
    {
        $user->refresh();

        if ($user->isVerifiedAdmin()) {
            return redirect('/admin');
        }

        if ($user->profile_completion_status === AccountEnrollmentState::PENDING) {
            return to_route('account.pending-review');
        }

        if ($user->profile_completion_status === AccountEnrollmentState::ACTIVE && $user->hasVerifiedEmail() && app(ProfileCompletionService::class)->isComplete($user)) {
            return redirect()->route('account.dashboard');
        }

        return redirect()->route('account.incomplete-profile');
    }
}
