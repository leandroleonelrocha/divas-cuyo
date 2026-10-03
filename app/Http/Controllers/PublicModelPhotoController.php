<?php

namespace App\Http\Controllers;

use App\Services\PublicModelPhotoService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PublicModelPhotoController extends Controller
{
    public function __invoke(string $publicToken, PublicModelPhotoService $photos): Response|StreamedResponse
    {
        $stream = $photos->openByToken($publicToken);
        if (! is_resource($stream)) {
            return response()->view('errors.404', [], 404, ['Cache-Control' => 'private, no-store']);
        }

        return response()->stream(function () use ($stream): void {
            try {
                if (fpassthru($stream) === false) {
                    Log::warning('public_photo_stream_failed');
                }
            } catch (Throwable) {
                Log::warning('public_photo_stream_failed');
            } finally {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => 'image/webp',
            'Content-Disposition' => 'inline; filename="photo.webp"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
