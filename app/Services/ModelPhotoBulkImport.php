<?php

namespace App\Services;

use App\Models\ModelProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Throwable;

/**
 * Importación masiva de fotos desde una carpeta extraída.
 *
 * Cada subcarpeta identifica un perfil por ID (numérico) o por slug:
 *
 *   8/
 *     01.jpg  02.jpg ...
 *   agus-del-este/
 *     01.jpg ...
 */
class ModelPhotoBulkImport
{
    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(private readonly ModelPhotoService $photos) {}

    public function resolveProfile(string $folder): ?ModelProfile
    {
        if (ctype_digit($folder)) {
            $byId = ModelProfile::query()->whereKey((int) $folder)->first();
            if ($byId) {
                return $byId;
            }
        }

        return ModelProfile::query()->where('slug', $folder)->first()
            ?? ModelProfile::query()->where('slug', Str::slug($folder))->first();
    }

    /** @return list<string> */
    public function imageFiles(string $dir): array
    {
        $files = [];
        foreach (glob($dir.DIRECTORY_SEPARATOR.'*') ?: [] as $path) {
            if (is_file($path) && in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::EXTENSIONS, true)) {
                $files[] = $path;
            }
        }
        sort($files);

        return array_values($files);
    }

    /**
     * @return list<array{folder: string, profileId: ?int, stageName: ?string, files: int, warning: ?string}>
     */
    public function preview(string $baseDir): array
    {
        $rows = [];
        foreach ($this->folders($baseDir) as $dir) {
            $folder = basename($dir);
            $profile = $this->resolveProfile($folder);
            $files = $this->imageFiles($dir);
            $rows[] = [
                'folder' => $folder,
                'profileId' => $profile?->getKey(),
                'stageName' => $profile?->stage_name,
                'files' => count($files),
                'warning' => $profile === null
                    ? 'Sin perfil (usá su ID o slug como nombre de carpeta).'
                    : ($files === [] ? 'Carpeta sin imágenes JPG/PNG/WebP.'
                        : ($profile->user && ! $profile->user->hasVerifiedEmail() ? 'Usuario sin email verificado.' : null)),
            ];
        }

        return $rows;
    }

    /**
     * @param bool $watermark Si es false no se aplica la marca del sitio (fotos que ya vienen marcadas).
     * @return array{imported: int, omitted: int, profiles: int}
     */
    public function import(string $baseDir, User $reviewer, bool $approve, bool $watermark = true): array
    {
        // Codificar WebP de fotos grandes con GD es lento: se amplía el
        // presupuesto del proceso (si el entorno lo permite).
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        $imported = 0;
        $omitted = 0;
        $profiles = 0;
        foreach ($this->folders($baseDir) as $dir) {
            $profile = $this->resolveProfile(basename($dir));
            $files = $this->imageFiles($dir);
            if (! $profile || $files === []) {
                $omitted += count($files);
                continue;
            }
            [$done, $skipped] = $this->importProfile($profile, $files, $approve ? $reviewer : null, $watermark);
            $imported += $done;
            $omitted += $skipped;
            if ($done > 0) {
                $profiles++;
            }
        }

        return ['imported' => $imported, 'omitted' => $omitted, 'profiles' => $profiles];
    }

    /** @return list<string> */
    private function folders(string $baseDir): array
    {
        $dirs = array_values(array_filter(
            glob($baseDir.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR) ?: [],
            fn (string $dir): bool => basename($dir) !== '' && ! str_starts_with(basename($dir), '.'),
        ));
        sort($dirs);

        return $dirs;
    }

    /**
     * @param list<string> $files
     * @return array{0: int, 1: int}
     */
    private function importProfile(ModelProfile $profile, array $files, ?User $reviewer, bool $watermark = true): array
    {
        $user = $profile->user;
        if (! $user || ! $user->hasVerifiedEmail()) {
            return [0, count($files)];
        }

        $done = 0;
        $skipped = 0;
        $firstApproved = null;
        foreach ($files as $path) {
            try {
                $photo = $this->photos->upload($user, new UploadedFile($path, basename($path), null, null, true), $watermark);
                if ($reviewer) {
                    $photo = $this->photos->moderatePendingVersion($photo, $reviewer, 'approved');
                    $firstApproved ??= $photo;
                }
                $done++;
            } catch (Throwable) {
                $skipped++;
            }
        }

        if ($firstApproved && ! $profile->photos()->where('is_primary', true)->exists()) {
            $this->photos->setPrimaryForAuthenticatedModel($user, $firstApproved);
        }

        return [$done, $skipped];
    }
}
