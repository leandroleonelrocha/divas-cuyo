<?php

namespace App\Http\Controllers;

use App\Services\PublicModelProfileQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class PublicModelProfileController extends Controller
{
    public function __invoke(string $slug, PublicModelProfileQuery $query): Response|RedirectResponse
    {
        ['profile' => $publicProfile, 'similar' => $similarProfiles] = $query->findWithSimilar($slug);

        if ($publicProfile && route('public.models.show', ['slug' => $slug]) !== $publicProfile->canonicalUrl) {
            return redirect()->to($publicProfile->canonicalUrl, 302)->header('Cache-Control', 'private, no-store');
        }

        if (! $publicProfile) {
            return response()->view('errors.404', [], 404, ['Cache-Control' => 'private, no-store']);
        }

        return response()->view(
            'public.models.show',
            ['publicProfile' => $publicProfile, 'similarProfiles' => $similarProfiles],
            200,
            ['Cache-Control' => 'private, no-store'],
        );
    }
}
