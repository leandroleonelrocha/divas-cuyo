<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountProfileUpdateRequest;
use App\Http\Requests\ModelProfileBioRequest;
use App\Http\Requests\ModelProfilePhysicalRevisionModerationRequest;
use App\Http\Requests\ModelProfilePhysicalRevisionRequest;
use App\Http\Requests\ModelProfileServicesUpdateRequest;
use App\Http\Requests\PublicationTypeChangeRequest;
use App\Models\ModelProfileBio;
use App\Models\ModelProfilePhysicalRevision;
use App\Models\Province;
use App\Models\PublicationType;
use App\Models\Service;
use App\Services\ModelProfileBioModerationService;
use App\Services\ModelProfileDetailsService;
use App\Services\ModelProfilePhysicalModerationService;
use App\Services\PublicationTypeChangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AccountProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $profile = $request->user()->modelProfile()->with([
            'privateDetails',
            'pendingPhysicalRevision',
            'province',
            'locality',
            'publicationType',
            'services',
            'bios',
            'currentBio',
        ])->firstOrFail();

        $provinces = Province::query()
            ->active()
            ->with(['localities' => fn ($query) => $query->active()->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('account.profile', [
            'user' => $request->user(),
            'profile' => $profile,
            'privateDetails' => $profile->privateDetails,
            'pendingPhysicalRevision' => $profile->pendingPhysicalRevision,
            'provinces' => $provinces,
            'publicationTypes' => PublicationType::query()->active()->orderBy('name')->get(),
            'services' => Service::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function update(AccountProfileUpdateRequest $request, ModelProfileDetailsService $service): RedirectResponse
    {
        $service->update(
            $request->user()->modelProfile,
            $request->user(),
            $request->validated(),
        );

        return to_route('account.profile.edit')->with('success', 'Tu información se guardó correctamente.');
    }

    public function storePhysicalRevision(
        ModelProfilePhysicalRevisionRequest $request,
        ModelProfilePhysicalModerationService $service,
    ): RedirectResponse {
        $service->submit(
            $request->user()->modelProfile,
            $request->user(),
            $request->validated(),
        );

        return to_route('account.profile.edit')->with('success', 'Tu modificación física fue enviada a revisión.');
    }

    public function changePublicationType(
        PublicationTypeChangeRequest $request,
        PublicationTypeChangeService $service,
    ): RedirectResponse {
        $service->change(
            $request->user()->modelProfile,
            PublicationType::query()->findOrFail($request->integer('publication_type_id')),
            $request->user(),
            'model',
            $request->validated('reason'),
            (bool) $request->validated('confirm_in_person_removal', false),
        );

        return to_route('account.profile.edit')->with('success', 'El tipo de publicación se actualizó correctamente.');
    }

    public function updateServices(
        ModelProfileServicesUpdateRequest $request,
        PublicationTypeChangeService $service,
    ): RedirectResponse {
        $service->syncServices(
            $request->user()->modelProfile,
            $request->validated('service_ids', []),
        );

        return to_route('account.profile.edit')->with('success', 'Los servicios se actualizaron correctamente.');
    }

    public function storeBio(
        ModelProfileBioRequest $request,
        ModelProfileBioModerationService $service,
    ): RedirectResponse {
        $service->submit(
            $request->user()->modelProfile,
            $request->user(),
            $request->string('content')->toString(),
        );

        return to_route('account.profile.edit')->with('success', 'Tu biografía fue enviada a aprobación.');
    }

    public function updateBio(
        ModelProfileBioRequest $request,
        ModelProfileBio $bio,
        ModelProfileBioModerationService $service,
    ): RedirectResponse {
        Gate::authorize('update', $bio);
        $service->resubmit(
            $bio,
            $request->user(),
            $request->string('content')->toString(),
        );

        return to_route('account.profile.edit')->with('success', 'Tu biografía corregida fue enviada a aprobación.');
    }

    public function approvePhysicalRevision(
        ModelProfilePhysicalRevision $revision,
        ModelProfilePhysicalModerationService $service,
    ): RedirectResponse {
        Gate::authorize('approve', $revision);
        $service->approve($revision, request()->user());

        return back()->with('success', 'La modificación física fue aprobada.');
    }

    public function rejectPhysicalRevision(
        ModelProfilePhysicalRevisionModerationRequest $request,
        ModelProfilePhysicalRevision $revision,
        ModelProfilePhysicalModerationService $service,
    ): RedirectResponse {
        Gate::authorize('reject', $revision);
        $service->reject($revision, $request->user(), $request->string('reason')->toString());

        return back()->with('success', 'La modificación física fue rechazada.');
    }
}
