<?php

namespace App\Http\Controllers;

use App\Services\PublicModelProfileQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class PublicModelProfileController extends Controller
{
    public function __invoke(string $slug, PublicModelProfileQuery $query): Response|RedirectResponse
    {
        $publicProfile = $query->findBySlug($slug);

        if ($publicProfile && route('public.models.show', ['slug' => $slug]) !== $publicProfile->canonicalUrl) {
            return redirect()->to($publicProfile->canonicalUrl, 302)->header('Cache-Control', 'private, no-store');
        }

        return response()->view(
            $publicProfile ? 'public.models.show' : 'errors.404',
            $publicProfile ? ['publicProfile' => $publicProfile] : [],
            $publicProfile ? 200 : 404,
            ['Cache-Control' => 'private, no-store'],
        );
    }
}
