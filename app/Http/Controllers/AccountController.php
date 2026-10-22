<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteProfileRequest;
use App\Models\Province;
use App\Models\PublicationType;
use App\Models\User;
use App\Services\AccountEnrollmentState;
use App\Services\AuthenticatedUserRedirector;
use App\Services\ProfileCompletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AccountController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user()->refresh()->load('modelProfile.province', 'modelProfile.locality', 'modelProfile.publicationType', 'modelProfile.currentBio');
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

    public function incompleteProfile(Request $request, ProfileCompletionService $completion)
    {
        $user = $request->user();
        Gate::forUser($user)->authorize('view', $user);

        abort_unless($user->hasVerifiedEmail(), 403);

        if ($completion->isComplete($user)) {
            return to_route('account.dashboard');
        }

        return view('account.incomplete-profile', [
            'user' => $user,
            'profile' => $user->modelProfile,
            'hasInPersonServices' => $user->modelProfile?->services()->where('service_type', 'in_person')->exists() ?? false,
            'provinces' => Province::query()->active()->with(['localities' => fn ($query) => $query->active()->orderBy('name')])->orderBy('name')->get(),
            'publicationTypes' => PublicationType::query()->active()
                ->whereIn('slug', array_keys(config('publication.monthly_prices_ars')))->orderBy('name')->get(),
        ]);
    }

    public function completeProfile(CompleteProfileRequest $request, ProfileCompletionService $completion)
    {
        $completion->complete($request->user(), $request->validated());

        return app(AuthenticatedUserRedirector::class)->redirect($request->user());
    }

    public function pendingReview(Request $request, AuthenticatedUserRedirector $redirector)
    {
        $user = $request->user()->refresh();
        if ($user->isVerifiedAdmin() || $user->profile_completion_status !== AccountEnrollmentState::PENDING) {
            return $redirector->redirect($user);
        }

        return view('account.pending-review');
    }

    public function show(User $user)
    {
        Gate::authorize('view', $user);

        $user->load('modelProfile.province', 'modelProfile.locality', 'modelProfile.publicationType', 'modelProfile.currentBio');

        return view('account.show', ['user' => $user]);
    }
}
