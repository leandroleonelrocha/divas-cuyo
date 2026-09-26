<?php

namespace App\Http\Controllers;

use App\Http\Requests\ModelPhotoReorderRequest;
use App\Http\Requests\ModelPhotoReplaceRequest;
use App\Http\Requests\ModelPhotoUploadRequest;
use App\Models\ModelPhoto;
use App\Models\ModelPhotoVersion;
use App\Services\ModelPhotoService;
use App\Services\ModelPhotoStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ModelPhotoController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        Gate::forUser($user)->authorize('viewAny', ModelPhoto::class);
        $profile = $user->modelProfile()->firstOrFail();
        $photos = $profile->photos()->with(['latestVersion', 'currentVersion'])->get();

        return view('account.photos.index', compact('user', 'profile', 'photos'));
    }

    public function store(ModelPhotoUploadRequest $request, ModelPhotoService $service)
    {
        $profile = $request->user()->modelProfile()->firstOrFail();
        Gate::forUser($request->user())->authorize('create', [ModelPhoto::class, $profile]);
        $service->uploadForAuthenticatedModel($request->user(), $request->file('photo'));

        return to_route('account.photos.index')->with('status', 'La fotografía se cargó y quedó en revisión.');
    }

    public function order(ModelPhotoReorderRequest $request, ModelPhotoService $service)
    {
        $profile = $request->user()->modelProfile()->firstOrFail();
        Gate::forUser($request->user())->authorize('reorder', [ModelPhoto::class, $profile]);
        $service->reorderForAuthenticatedModel($request->user(), $request->validated('photo_ids'));

        return to_route('account.photos.index')->with('status', 'El orden de tus fotografías se actualizó.');
    }

    public function setPrimary(Request $request, ModelPhoto $photo, ModelPhotoService $service)
    {
        Gate::forUser($request->user())->authorize('setPrimary', $photo);
        $service->setPrimaryForAuthenticatedModel($request->user(), $photo);

        return to_route('account.photos.index')->with('status', 'La fotografía seleccionada ahora es la principal.');
    }

    public function replace(ModelPhotoReplaceRequest $request, ModelPhoto $photo, ModelPhotoService $service)
    {
        Gate::forUser($request->user())->authorize('replace', $photo);
        $service->replaceForAuthenticatedModel($request->user(), $photo, $request->file('photo'));

        return to_route('account.photos.index')->with('status', 'La nueva versión se cargó y quedó en revisión.');
    }

    public function destroy(Request $request, ModelPhoto $photo, ModelPhotoService $service)
    {
        Gate::forUser($request->user())->authorize('delete', $photo);
        $service->deleteForAuthenticatedModel($request->user(), $photo);

        return to_route('account.photos.index')->with('status', 'La fotografía se eliminó correctamente.');
    }

    public function file(Request $request, ModelPhoto $photo, string $variant): StreamedResponse
    {
        Gate::forUser($request->user())->authorize('view', $photo);

        abort_unless(in_array($variant, ['thumbnail', 'processed', 'public'], true), 404);

        $photo->loadMissing(['currentVersion', 'latestVersion']);
        $version = $photo->currentVersion ?? $photo->latestVersion;
        abort_unless($version, 404);

        $path = match ($variant) {
            'thumbnail' => $version->thumbnail_path,
            'processed' => $version->processed_path,
            'public' => $version->public_path,
        };
        $storage = app(ModelPhotoStorage::class);
        abort_unless($storage->isSafePath($path), 404);
        abort_unless($storage->disk()->exists($path), 404);

        return response()->stream(function () use ($storage, $path): void {
            $stream = $storage->readStream($path);
            abort_unless(is_resource($stream), 404);
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => 'image/webp',
            'Content-Disposition' => 'inline; filename="photo.webp"',
            'Cache-Control' => 'private, no-store',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function adminFile(Request $request, ModelPhoto $photo, ModelPhotoVersion $version, string $variant): StreamedResponse
    {
        Gate::forUser($request->user())->authorize('deliverPrivate', $photo);

        abort_unless($version->model_photo_id === $photo->getKey(), 404);
        abort_unless(in_array($variant, ['thumbnail', 'processed', 'public'], true), 404);

        $path = match ($variant) {
            'thumbnail' => $version->thumbnail_path,
            'processed' => $version->processed_path,
            'public' => $version->public_path,
        };
        $storage = app(ModelPhotoStorage::class);
        abort_unless($path && $storage->isSafePath($path), 404);
        abort_unless($storage->disk()->exists($path), 404);

        return response()->stream(function () use ($storage, $path): void {
            $stream = $storage->readStream($path);
            abort_unless(is_resource($stream), 404);
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => 'image/webp',
            'Content-Disposition' => 'inline; filename="photo.webp"',
            'Cache-Control' => 'private, no-store',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
