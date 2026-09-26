<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

class AuthenticatedUserRedirector
{
    public function redirect(User $user): RedirectResponse
    {
        if ($user->isVerifiedAdmin()) {
            return redirect('/admin');
        }

        if ($user->hasVerifiedEmail() && $user->modelProfile()->exists()) {
            return redirect()->route('account.dashboard');
        }

        return redirect()->route('account.incomplete-profile');
    }
}
