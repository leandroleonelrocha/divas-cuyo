<?php

namespace App\Services\VideoHosting;

use App\Contracts\VideoHostingProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LocalVideoHostingProvider implements VideoHostingProvider
{
    private function path(string $resourceId, string $variant): string
    {
        abort_unless(Str::isUuid($resourceId) && in_array($variant, ['video', 'poster'], true), 404);

        return 'hosted/'.$resourceId.'/'.($variant === 'video' ? 'video.mp4' : 'poster.jpg');
    }

    public function store(string $resourceId, string $videoPath, string $posterPath): void
    {
        foreach (['video' => $videoPath, 'poster' => $posterPath] as $variant => $source) {
            $stream = fopen($source, 'rb');
            try {
                Storage::disk('model_videos')->put($this->path($resourceId, $variant), $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }
    }

    public function available(string $resourceId): bool
    {
        foreach (['video', 'poster'] as $variant) {
            if (! Storage::disk('model_videos')->exists($this->path($resourceId, $variant))) {
                return false;
            }
        }

        return true;
    }

    public function response(string $resourceId, string $variant): Response
    {
        $path = $this->path($resourceId, $variant);
        abort_unless(Storage::disk('model_videos')->exists($path), 404);

        // BinaryFileResponse supports byte ranges for seeking, without buffering the video in PHP.
        return response()->file(Storage::disk('model_videos')->path($path), [
            'Content-Type' => $variant === 'video' ? 'video/mp4' : 'image/jpeg',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function delete(string $resourceId): void
    {
        foreach (['video', 'poster'] as $variant) {
            Storage::disk('model_videos')->delete($this->path($resourceId, $variant));
        }
    }

    public function export(string $resourceId, string $directory): void
    {
        foreach (['video' => 'video.mp4', 'poster' => 'poster.jpg'] as $variant => $filename) {
            if (! copy(Storage::disk('model_videos')->path($this->path($resourceId, $variant)), $directory.'/'.$filename)) {
                throw new \RuntimeException('No se pudo exportar el video.');
            }
        }
    }
}
