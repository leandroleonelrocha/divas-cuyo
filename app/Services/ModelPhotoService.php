<?php

namespace App\Services;

use App\Models\ModelPhoto;
use App\Models\ModelPhotoVersion;
use App\Models\ModelProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ModelPhotoService
{
    public function __construct(
        private readonly ModelPhotoImageProcessor $processor,
        private readonly ModelPhotoStorage $storage,
        private readonly ModelPhotoDomainRules $rules,
    ) {}

    public function uploadForAuthenticatedModel(User $user, UploadedFile $file): ModelPhoto
    {
        $profile = $user->modelProfile()->first();
        abort_unless($profile && $user->hasVerifiedEmail(), 403);
        $writtenPaths = [];

        try {
            $result = DB::transaction(function () use ($profile, $file, &$writtenPaths): ModelPhoto {
                $lockedProfile = ModelProfile::query()->lockForUpdate()->findOrFail($profile->getKey());
                $count = ModelPhoto::query()->where('model_profile_id', $lockedProfile->getKey())->count();
                if ($count >= $this->rules->maxPhotos()) {
                    throw ValidationException::withMessages(['photo' => 'Alcanzaste el máximo de 5 fotografías.']);
                }

                $processed = $this->processor->process($file);
                $paths = $this->storage->storeVariants(
                    (int) $lockedProfile->getKey(),
                    $processed['contents'],
                    $processed['extension'],
                );
                $writtenPaths = array_values($paths);

                $photo = ModelPhoto::query()->create([
                    'model_profile_id' => $lockedProfile->getKey(),
                    'position' => ((int) ModelPhoto::query()->where('model_profile_id', $lockedProfile->getKey())->max('position')) + 1,
                    'is_primary' => false,
                ]);
                $version = $photo->versions()->create([
                    ...$paths,
                    'version' => 1,
                    'original_name' => mb_substr($processed['original_name'], 0, 255),
                    'mime_type' => $processed['mime_type'],
                    'file_size' => $processed['file_size'],
                    'width' => $processed['width'],
                    'height' => $processed['height'],
                    'processed_width' => $processed['processed_width'],
                    'processed_height' => $processed['processed_height'],
                    'status' => 'pending',
                ]);

                $version->forceFill(['public_watermarked_at' => $processed['public_watermarked'] ? now() : null])->save();

                return $photo->load('latestVersion');
            });

            return $result;
        } catch (Throwable $exception) {
            $this->storage->deletePaths($writtenPaths);
            throw $exception;
        }
    }

    public function upload(User $user, UploadedFile $file): ModelPhoto
    {
        return $this->uploadForAuthenticatedModel($user, $file);
    }

    public function reorderForAuthenticatedModel(User $user, array $photoIds): void
    {
        $profile = $user->modelProfile()->first();
        abort_unless($profile && $user->hasVerifiedEmail(), 403);

        DB::transaction(function () use ($profile, $photoIds): void {
            $lockedProfile = ModelProfile::query()->lockForUpdate()->findOrFail($profile->getKey());
            $ownedPhotos = ModelPhoto::query()
                ->where('model_profile_id', $lockedProfile->getKey())
                ->lockForUpdate()
                ->get(['id', 'position']);
            $requestedIds = array_map('intval', $photoIds);
            $ownedIds = $ownedPhotos->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all();
            $requestedSorted = collect($requestedIds)->sort()->values()->all();

            if (count($requestedIds) !== count(array_unique($requestedIds))
                || $requestedSorted !== $ownedIds) {
                throw ValidationException::withMessages([
                    'photo_ids' => 'El orden debe incluir exactamente tus fotografías, una sola vez cada una.',
                ]);
            }

            $temporaryOffset = count($requestedIds) + 1;
            ModelPhoto::query()
                ->where('model_profile_id', $lockedProfile->getKey())
                ->increment('position', $temporaryOffset);

            foreach ($requestedIds as $position => $photoId) {
                ModelPhoto::query()
                    ->where('model_profile_id', $lockedProfile->getKey())
                    ->whereKey($photoId)
                    ->update(['position' => $position]);
            }
        });
    }

    public function setPrimaryForAuthenticatedModel(User $user, ModelPhoto $photo): void
    {
        $profile = $user->modelProfile()->first();
        abort_unless($profile && $user->hasVerifiedEmail(), 403);

        DB::transaction(function () use ($profile, $photo): void {
            $lockedProfile = ModelProfile::query()->lockForUpdate()->findOrFail($profile->getKey());
            $lockedPhoto = ModelPhoto::query()
                ->where('model_profile_id', $lockedProfile->getKey())
                ->lockForUpdate()
                ->findOrFail($photo->getKey());
            $version = $lockedPhoto->currentVersion()->first();

            if (! $version || $version->status->value !== 'approved') {
                throw ValidationException::withMessages([
                    'photo' => 'Sólo una fotografía aprobada puede ser principal.',
                ]);
            }

            ModelPhoto::query()
                ->where('model_profile_id', $lockedProfile->getKey())
                ->update(['is_primary' => false]);
            $lockedPhoto->forceFill(['is_primary' => true])->save();
        });
    }

    public function replaceForAuthenticatedModel(User $user, ModelPhoto $photo, UploadedFile $file): ModelPhoto
    {
        $profile = $user->modelProfile()->first();
        abort_unless($profile && $user->hasVerifiedEmail(), 403);
        $writtenPaths = [];

        try {
            $result = DB::transaction(function () use ($profile, $photo, $file, &$writtenPaths): ModelPhoto {
                $lockedProfile = ModelProfile::query()->lockForUpdate()->findOrFail($profile->getKey());
                $lockedPhoto = ModelPhoto::query()
                    ->where('model_profile_id', $lockedProfile->getKey())
                    ->lockForUpdate()
                    ->findOrFail($photo->getKey());
                $processed = $this->processor->process($file);
                $paths = $this->storage->storeVariants(
                    (int) $lockedProfile->getKey(),
                    $processed['contents'],
                    $processed['extension'],
                );
                $writtenPaths = array_values($paths);
                $nextVersion = ((int) $lockedPhoto->versions()->max('version')) + 1;
                $currentVersion = $lockedPhoto->currentVersion()->first();

                $version = $lockedPhoto->versions()->create([
                    ...$paths,
                    'version' => $nextVersion,
                    'supersedes_version_id' => $currentVersion?->getKey(),
                    'original_name' => mb_substr($processed['original_name'], 0, 255),
                    'mime_type' => $processed['mime_type'],
                    'file_size' => $processed['file_size'],
                    'width' => $processed['width'],
                    'height' => $processed['height'],
                    'processed_width' => $processed['processed_width'],
                    'processed_height' => $processed['processed_height'],
                    'status' => 'pending',
                    'rejection_reason' => null,
                    'reviewed_at' => null,
                    'reviewed_by' => null,
                ]);

                $version->forceFill(['public_watermarked_at' => $processed['public_watermarked'] ? now() : null])->save();

                return $lockedPhoto->load(['currentVersion', 'latestVersion']);
            });

            return $result;
        } catch (Throwable $exception) {
            $this->storage->deletePaths($writtenPaths);
            throw $exception;
        }
    }

    public function deleteForAuthenticatedModel(User $user, ModelPhoto $photo): void
    {
        $profile = $user->modelProfile()->first();
        abort_unless($profile && $user->hasVerifiedEmail(), 403);
        $paths = [];

        DB::transaction(function () use ($profile, $photo, &$paths): void {
            $lockedProfile = ModelProfile::query()->lockForUpdate()->findOrFail($profile->getKey());
            $lockedPhoto = ModelPhoto::query()
                ->where('model_profile_id', $lockedProfile->getKey())
                ->with('versions')
                ->lockForUpdate()
                ->findOrFail($photo->getKey());
            $wasPrimary = $lockedPhoto->is_primary;
            $paths = $lockedPhoto->versions
                ->flatMap(fn ($version): Collection => collect([
                    $version->original_path,
                    $version->processed_path,
                    $version->public_path,
                    $version->thumbnail_path,
                ]))
                ->filter()
                ->values()
                ->all();

            foreach ($paths as $path) {
                if (! $this->storage->isSafePath($path)) {
                    throw new RuntimeException('La fotografía contiene una ruta de almacenamiento inválida.');
                }
            }

            $lockedPhoto->delete();
            $remaining = ModelPhoto::query()
                ->where('model_profile_id', $lockedProfile->getKey())
                ->orderBy('position')
                ->lockForUpdate()
                ->get();
            $temporaryOffset = $remaining->count() + 1;
            ModelPhoto::query()
                ->where('model_profile_id', $lockedProfile->getKey())
                ->increment('position', $temporaryOffset);
            foreach ($remaining as $position => $remainingPhoto) {
                $remainingPhoto->forceFill([
                    'position' => $position,
                    'is_primary' => false,
                ])->save();
            }

            if ($wasPrimary) {
                $replacement = $remaining->first(function (ModelPhoto $remainingPhoto): bool {
                    return $remainingPhoto->currentVersion()->where('status', 'approved')->exists();
                });
                $replacement?->forceFill(['is_primary' => true])->save();
            }
        });

        $this->storage->deletePaths($paths);
    }

    public function promoteApprovedVersion(ModelPhotoVersion $version): ModelPhoto
    {
        $oldPaths = [];
        $result = DB::transaction(function () use ($version, &$oldPaths): ModelPhoto {
            $lockedVersion = ModelPhotoVersion::query()->lockForUpdate()->findOrFail($version->getKey());
            $lockedPhoto = ModelPhoto::query()
                ->lockForUpdate()
                ->findOrFail($lockedVersion->model_photo_id);

            if ($lockedVersion->status->value !== 'approved') {
                throw ValidationException::withMessages(['photo' => 'Sólo una versión aprobada puede convertirse en vigente.']);
            }

            $currentVersion = $lockedPhoto->currentVersion()->lockForUpdate()->first();
            if ($currentVersion && $currentVersion->is($lockedVersion)) {
                return $lockedPhoto->load(['currentVersion', 'latestVersion']);
            }

            $latestVersion = $lockedPhoto->latestVersion()->lockForUpdate()->first();
            if (! $latestVersion || ! $latestVersion->is($lockedVersion)) {
                throw ValidationException::withMessages(['photo' => 'La versión aprobada está obsoleta y no puede convertirse en vigente.']);
            }

            if ($currentVersion) {
                $oldPaths = [
                    $currentVersion->original_path,
                    $currentVersion->processed_path,
                    $currentVersion->public_path,
                    $currentVersion->thumbnail_path,
                ];
                $currentVersion->delete();
            }

            $lockedPhoto->forceFill(['current_version_id' => $lockedVersion->getKey()])->save();

            return $lockedPhoto->fresh(['currentVersion', 'latestVersion']);
        });

        if ($oldPaths !== []) {
            $this->storage->deletePaths($oldPaths);
        }

        return $result;
    }

    public function moderatePendingVersion(ModelPhoto $photo, User $reviewer, string $status, ?string $rejectionReason = null): ModelPhoto
    {
        abort_unless($reviewer->isVerifiedAdmin(), 403);

        if (! in_array($status, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages(['photo' => 'La transición de moderación no es válida.']);
        }

        if ($status === 'rejected' && blank(trim((string) $rejectionReason))) {
            throw ValidationException::withMessages(['rejection_reason' => 'El motivo del rechazo es obligatorio.']);
        }

        $version = null;
        $result = DB::transaction(function () use ($photo, $reviewer, $status, $rejectionReason, &$version): ModelPhoto {
            $lockedPhoto = ModelPhoto::query()->lockForUpdate()->findOrFail($photo->getKey());
            $version = $lockedPhoto->latestVersion()->lockForUpdate()->first();

            if (! $version || $version->status->value !== 'pending') {
                throw ValidationException::withMessages(['photo' => 'La fotografía ya no está pendiente de revisión.']);
            }

            foreach ([$version->processed_path, $version->public_path, $version->thumbnail_path] as $path) {
                if (! $path || ! $this->storage->disk()->exists($path)) {
                    throw ValidationException::withMessages(['photo' => 'La variante de la fotografía no está disponible.']);
                }
            }

            $version->forceFill([
                'status' => $status,
                'rejection_reason' => $status === 'rejected' ? trim((string) $rejectionReason) : null,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->getKey(),
            ])->save();

            return $lockedPhoto->fresh(['currentVersion', 'latestVersion']);
        });

        if ($status === 'approved' && $version) {
            return $this->promoteApprovedVersion($version->fresh());
        }

        return $result;
    }
}
