<?php

namespace App\Http\Controllers;

use App\Models\ModelVideo;
use App\Services\ModelVideoService;
use App\Services\PublicModelProfileVisibility;
use App\Services\VideoHosting\VideoHostingManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ModelVideoController extends Controller
{
    public function index(Request $request)
    {
        $profile = $request->user()->modelProfile()->firstOrFail();

        return view('account.videos.index', ['profile' => $profile, 'videos' => $profile->videos()->get()]);
    }

    public function store(Request $request, ModelVideoService $service)
    {
        $request->validate(['video' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime', 'extensions:mp4,mov', 'max:'.config('model-videos.max_size_kb')]]);
        $service->upload($request->user(), $request->file('video'));

        return to_route('account.videos.index')->with('status', 'Video recibido. Vamos a procesarlo con marca de agua antes de revisarlo.');
    }

    public function destroy(Request $request, ModelVideo $video, ModelVideoService $service)
    {
        $service->delete($request->user(), $video);

        return to_route('account.videos.index')->with('status', 'El video se eliminó del perfil.');
    }

    public function retry(Request $request, ModelVideo $video, ModelVideoService $service)
    {
        $service->retry($request->user(), $video);

        return to_route('account.videos.index')->with('status', 'Volveremos a procesar el video.');
    }

    public function file(ModelVideo $video, string $variant, VideoHostingManager $hosting)
    {
        Gate::authorize('view', $video);
        abort_unless($video->ready(), 404);

        return $hosting->provider($video->provider)->response($video->resource_id, $variant);
    }

    public function adminFile(ModelVideo $video, string $variant, VideoHostingManager $hosting)
    {
        Gate::authorize('moderate', $video);

        return $this->file($video, $variant, $hosting);
    }

    public function publicFile(string $token, string $variant, PublicModelProfileVisibility $visibility, VideoHostingManager $hosting)
    {
        $video = ModelVideo::where('public_token', $token)->firstOrFail();
        abort_unless($video->ready() && $video->moderation_status === 'approved'
            && $visibility->publiclyVisible($video->modelProfile), 404);

        return $hosting->provider($video->provider)->response($video->resource_id, $variant);
    }
}
