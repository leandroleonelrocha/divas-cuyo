<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PrivateIdentityDocumentStorage
{
    public function store(UploadedFile $file, int $profileId, string $mime): string
    {
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        };
        $path = "profiles/{$profileId}/".Str::random(40).".{$extension}";

        $stored = Storage::disk($this->disk())->put($path, $file->getContent());

        if (! $stored) {
            throw new RuntimeException('No se pudo guardar el documento de forma segura.');
        }

        return $path;
    }

    public function delete(string $path): void
    {
        if (! $this->isSafePath($path)) {
            return;
        }

        Storage::disk($this->disk())->delete($path);
    }

    public function isSafePath(string $path): bool
    {
        return preg_match('/^profiles\/\d+\/[A-Za-z0-9]{40}\.(jpg|png|webp|pdf)$/', $path) === 1;
    }

    private function disk(): string
    {
        return config('identity-documents.disk');
    }
}
