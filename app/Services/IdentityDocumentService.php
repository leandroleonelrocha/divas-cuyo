<?php

namespace App\Services;

use App\Models\ModelDocument;
use App\Models\ModelProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

class IdentityDocumentService
{
    public function __construct(private readonly PrivateIdentityDocumentStorage $storage) {}

    public function upload(ModelProfile $profile, string $type, UploadedFile $file): ModelDocument
    {
        if (! in_array($profile->identity_status, ['incomplete', 'rejected'], true)) {
            throw new LogicException('La documentación no puede modificarse en el estado actual.');
        }

        if (! in_array($type, config('identity-documents.required_types'), true)) {
            throw new LogicException('El tipo de documento no está permitido.');
        }

        $mime = $file->getMimeType();
        if (! is_string($mime) || ! in_array($mime, config('identity-documents.allowed_mimes'), true)) {
            throw new LogicException('El documento no tiene un formato permitido.');
        }
        $path = $this->storage->store($file, $profile->getKey(), $mime);
        $previousPath = null;

        try {
            $document = DB::transaction(function () use ($profile, $type, $path, $file, $mime, &$previousPath): ModelDocument {
                $lockedProfile = ModelProfile::query()->lockForUpdate()->findOrFail($profile->getKey());

                if (! in_array($lockedProfile->identity_status, ['incomplete', 'rejected'], true)) {
                    throw new LogicException('La documentación no puede modificarse en el estado actual.');
                }

                $document = $lockedProfile->documents()->lockForUpdate()->where('type', $type)->first();
                $previousPath = $document?->storage_path;
                $attributes = [
                    'type' => $type,
                    'storage_path' => $path,
                    'original_name' => Str::limit(basename($file->getClientOriginalName()), 255, ''),
                    'mime_type' => $mime,
                    'file_size' => $file->getSize(),
                    'status' => 'pending',
                    'rejection_reason' => null,
                    'reviewed_at' => null,
                    'reviewed_by' => null,
                ];

                if ($document) {
                    $document->forceFill($attributes)->save();
                } else {
                    $document = new ModelDocument;
                    $document->modelProfile()->associate($lockedProfile);
                    $document->forceFill($attributes)->save();
                }

                return $document;
            });

            if ($previousPath && $previousPath !== $path) {
                $this->storage->delete($previousPath);
            }

            return $document;
        } catch (\Throwable $exception) {
            $this->storage->delete($path);

            throw $exception;
        }
    }

    public function submit(ModelProfile $profile): ModelProfile
    {
        return DB::transaction(function () use ($profile): ModelProfile {
            $locked = ModelProfile::query()->lockForUpdate()->findOrFail($profile->getKey());
            $required = collect(config('identity-documents.required_types'));
            $uploaded = $locked->documents()->whereIn('type', $required)->pluck('type');

            if ($required->diff($uploaded)->isNotEmpty()) {
                throw new LogicException('Debés cargar todos los documentos requeridos antes de enviar la documentación.');
            }

            if (! in_array($locked->identity_status, ['incomplete', 'rejected'], true)) {
                throw new LogicException('La documentación no puede enviarse en el estado actual.');
            }

            $locked->forceFill(['identity_status' => 'pending'])->save();

            return $locked->refresh();
        });
    }

    public function approveIdentity(ModelProfile $profile, User $reviewer): ModelProfile
    {
        return $this->resolveIdentity($profile, $reviewer, 'approved');
    }

    public function rejectIdentity(ModelProfile $profile, User $reviewer, string $reason): ModelProfile
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new LogicException('Debés indicar un motivo para rechazar la identidad.');
        }

        return $this->resolveIdentity($profile, $reviewer, 'rejected', $reason);
    }

    private function resolveIdentity(ModelProfile $profile, User $reviewer, string $status, ?string $reason = null): ModelProfile
    {
        return DB::transaction(function () use ($profile, $reviewer, $status, $reason): ModelProfile {
            $locked = ModelProfile::query()->lockForUpdate()->findOrFail($profile->getKey());
            $required = collect(config('identity-documents.required_types'));
            $documents = $locked->documents()->whereIn('type', $required)->lockForUpdate()->get();

            if ($locked->identity_status !== 'pending') {
                throw new LogicException('Sólo se puede resolver una identidad pendiente.');
            }

            if ($required->diff($documents->pluck('type'))->isNotEmpty()) {
                throw new LogicException('No se puede resolver la identidad porque faltan documentos obligatorios.');
            }

            $now = now();
            $documents->each(fn (ModelDocument $document) => $document->forceFill([
                'status' => $status,
                'rejection_reason' => $status === 'rejected' ? $reason : null,
                'reviewed_at' => $now,
                'reviewed_by' => $reviewer->getKey(),
            ])->save());

            $locked->forceFill([
                'identity_status' => $status,
                'identity_reviewed_at' => $now,
                'identity_reviewed_by' => $reviewer->getKey(),
                'identity_rejection_reason' => $status === 'rejected' ? $reason : null,
            ])->save();

            return $profile->refresh();
        });
    }
}
