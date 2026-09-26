<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AccountController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user()->load('modelProfile.province', 'modelProfile.locality', 'modelProfile.publicationType', 'modelProfile.currentBio');
        Gate::forUser($user)->authorize('view', $user);

        abort_unless($user->hasVerifiedEmail(), 403);

        if (! $user->modelProfile) {
            return to_route('account.incomplete-profile');
        }

        return view('account.dashboard', [
            'user' => $user,
            'profile' => $user->modelProfile,
        ]);
    }

    public function incompleteProfile(Request $request)
    {
        $user = $request->user();
        Gate::forUser($user)->authorize('view', $user);

        abort_unless($user->hasVerifiedEmail(), 403);

        if ($user->modelProfile()->exists()) {
            return to_route('account.dashboard');
        }

        return view('account.incomplete-profile', compact('user'));
    }

    public function show(User $user)
    {
        Gate::authorize('view', $user);

        $user->load('modelProfile.province', 'modelProfile.locality', 'modelProfile.publicationType', 'modelProfile.currentBio');

        return view('account.show', ['user' => $user]);
    }
}
