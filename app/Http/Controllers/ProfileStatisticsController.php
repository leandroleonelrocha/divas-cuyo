<?php

namespace App\Http\Controllers;

use App\Models\ModelProfile;
use App\Services\ProfileStatistics;
use App\Services\PublicModelProfileVisibility;
use Illuminate\Http\Request;

class ProfileStatisticsController extends Controller
{
    public function session()
    {
        return response()->json(['token' => csrf_token()])->header('Cache-Control', 'private, no-store');
    }

    public function record(Request $request, string $slug, ProfileStatistics $statistics, PublicModelProfileVisibility $visibility)
    {
        $data = $request->validate(['event' => ['required', 'in:visit,whatsapp']]);
        $profile = ModelProfile::query()->where('slug', $slug)->firstOrFail();
        if ($request->user()?->isAdmin() || $request->user()?->id === $profile->user_id
            || preg_match('/bot|crawler|spider|preview|headless|facebookexternalhit|WhatsApp/i', $request->userAgent() ?? '')
            || blank($request->userAgent())) {
            return response()->noContent();
        }
        abort_unless($visibility->publiclyVisible($profile), 404);
        if ($data['event'] === 'whatsapp' && blank(preg_replace('/\D/', '', $profile->whatsapp ?? ''))) {
            return response()->noContent();
        }
        $visitor = hash_hmac('sha256', $request->session()->getId(), config('app.key'));
        // A contact interaction also implies a visit, even if the initial request failed.
        $statistics->record($profile, $visitor, 'visit');
        if ($data['event'] === 'whatsapp') {
            $statistics->record($profile, $visitor, 'whatsapp');
        }

        return response()->noContent();
    }

    public function index(Request $request, ProfileStatistics $statistics)
    {
        $profile = $request->user()->modelProfile()->firstOrFail();
        abort_unless($profile->user_id === $request->user()->id, 403);
        $data = $request->validate(['period' => ['sometimes', 'in:7,30,month']]);

        return response()->view('account.statistics', [
            'profile' => $profile,
            'stats' => $statistics->summary($profile, $data['period'] ?? '30'),
        ])->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
