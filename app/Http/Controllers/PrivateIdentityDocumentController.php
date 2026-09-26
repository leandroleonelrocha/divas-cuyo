<?php

namespace App\Http\Controllers;

use App\Http\Requests\IdentityDocumentSubmissionRequest;
use App\Http\Requests\IdentityDocumentUploadRequest;
use App\Models\ModelDocument;
use App\Models\User;
use App\Services\IdentityDocumentService;
use App\Services\PrivateIdentityDocumentStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use LogicException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateIdentityDocumentController extends Controller
{
    public function show(Request $request, User $user): View
    {
        Gate::forUser($request->user())->authorize('view', $user);
        $profile = $user->modelProfile()->with('documents')->firstOrFail();

        return view('identity.show', compact('user', 'profile'));
    }

    public function store(IdentityDocumentUploadRequest $request, User $user, IdentityDocumentService $service): RedirectResponse
    {
        $profile = $user->modelProfile()->firstOrFail();
        Gate::authorize('create', [ModelDocument::class, $profile]);

        try {
            $service->upload($profile, $request->string('type')->toString(), $request->file('document'));
        } catch (LogicException $exception) {
            return back()->withErrors(['document' => $exception->getMessage()]);
        }

        return to_route('identity.show', $user)->with('status', 'Documento cargado correctamente.');
    }

    public function submit(IdentityDocumentSubmissionRequest $request, User $user, IdentityDocumentService $service): RedirectResponse
    {
        $profile = $user->modelProfile()->firstOrFail();

        try {
            $service->submit($profile);
        } catch (LogicException $exception) {
            return back()->withErrors(['documents' => $exception->getMessage()]);
        }

        return to_route('identity.show', $user)->with('status', 'La documentación fue enviada a revisión.');
    }

    public function download(Request $request, User $user, ModelDocument $document): StreamedResponse
    {
        Gate::authorize('download', $document);
        abort_unless($document->modelProfile?->user_id === $user->getKey(), 404);
        abort_unless(app(PrivateIdentityDocumentStorage::class)->isSafePath($document->storage_path), 404);

        return Storage::disk(config('identity-documents.disk'))->download(
            $document->storage_path,
            $this->downloadName($document),
            [
                'Content-Type' => $document->mime_type,
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    private function downloadName(ModelDocument $document): string
    {
        $originalName = basename($document->original_name ?: 'documento');
        $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $originalName) ?: 'documento';

        return substr($safeName, 0, 120);
    }
}
