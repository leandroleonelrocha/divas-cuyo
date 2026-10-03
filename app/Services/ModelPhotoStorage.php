<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ModelPhotoStorage
{
    public function isPublicPath(string $path): bool
    {
        return $this->isSafePath($path) && basename($path) === 'public.webp';
    }

    /** Open and check before sending headers; the caller owns a successful stream. */
    public function openPublicStream(string $path)
    {
        if (! $this->isPublicPath($path)) {
            return null;
        }

        $stream = null;
        try {
            $stream = $this->readStream($path);
            if (is_resource($stream) && ($byte = fread($stream, 1)) !== false && $byte !== '' && rewind($stream)) {
                return $stream;
            }
        } catch (\Throwable) {
            Log::warning('public_photo_unreadable');
        }

        if (is_resource($stream)) {
            fclose($stream);
        }

        return null;
    }

    public function storePublicVariant(string $sourcePath, string $contents): string
    {
        if (! $this->isSafePath($sourcePath) || basename($sourcePath) !== 'processed.webp') {
            throw new RuntimeException('Invalid public preparation source.');
        }

        $path = dirname($sourcePath).'/'.Str::uuid().'/public.webp';
        try {
            if (! $this->disk()->put($path, $contents)) {
                throw new RuntimeException('Public variant write failed.');
            }
        } catch (\Throwable $exception) {
            $this->deletePaths([$path]);
            throw $exception;
        }

        return $path;
    }

    public function disk()
    {
        return Storage::disk(config('model-photos.disk'));
    }

    /**
     * @param  array{original:string, processed:string, public:string, thumbnail:string}  $contents
     * @return array{original_path:string, processed_path:string, public_path:string, thumbnail_path:string}
     */
    public function storeVariants(int $profileId, array $contents, string $originalExtension): array
    {
        $profileToken = Str::lower((string) Str::uuid());
        $photoToken = Str::lower((string) Str::uuid());
        $versionToken = Str::lower((string) Str::uuid());
        $base = trim((string) config('model-photos.path_prefix'), '/')."/{$profileToken}/photos/{$photoToken}/{$versionToken}";

        $paths = [
            'original_path' => "{$base}/original.{$originalExtension}",
            'processed_path' => "{$base}/processed.webp",
            'public_path' => "{$base}/public.webp",
            'thumbnail_path' => "{$base}/thumbnail.webp",
        ];

        $written = [];

        try {
            foreach ($paths as $key => $path) {
                $content = $contents[str_replace('_path', '', $key)];
                if (! $this->disk()->put($path, $content)) {
                    throw new RuntimeException('No se pudo guardar una variante de la fotografía.');
                }
                $written[] = $path;
            }
        } catch (\Throwable $exception) {
            $this->deletePaths($written);
            throw $exception;
        }

        return $paths;
    }

    public function deletePaths(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_string($path) && $this->isSafePath($path)) {
                $this->disk()->delete($path);
            }
        }
    }

    public function isSafePath(string $path): bool
    {
        $normalized = ltrim($path, '/');
        $prefix = trim((string) config('model-photos.path_prefix'), '/');

        return $prefix !== ''
            && $normalized === $path
            && ! str_contains($normalized, '..')
            && ! str_contains($normalized, '//')
            && str_starts_with($normalized, $prefix.'/')
            && ! str_contains($normalized, '\\')
            && ! preg_match('/[\x00-\x1F\x7F]/', $normalized);
    }

    public function readStream(string $path)
    {
        abort_unless($this->isSafePath($path), 404);

        return $this->disk()->readStream($path);
    }
}
