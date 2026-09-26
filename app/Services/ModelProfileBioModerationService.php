<?php

namespace App\Services;

use App\Models\ModelProfile;
use App\Models\ModelProfileBio;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ModelProfileBioModerationService
{
    public function submit(ModelProfile $profile, User $owner, string $content): ModelProfileBio
    {
        $this->assertOwner($profile, $owner);

        return DB::transaction(function () use ($profile, $content): ModelProfileBio {
            $lockedProfile = ModelProfile::query()->whereKey($profile->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedProfile->bios()->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages([
                    'content' => 'Ya existe una biografía pendiente de aprobación.',
                ]);
            }

            return $lockedProfile->bios()->create([
                'content' => $content,
                'status' => 'pending',
            ]);
        });
    }

    public function resubmit(ModelProfileBio $bio, User $owner, string $content): ModelProfileBio
    {
        $profile = $bio->modelProfile;
        $this->assertOwner($profile, $owner);

        return DB::transaction(function () use ($bio, $profile, $content): ModelProfileBio {
            $lockedBio = ModelProfileBio::query()->whereKey($bio->getKey())->lockForUpdate()->firstOrFail();
            if ($lockedBio->status !== 'rejected') {
                throw ValidationException::withMessages([
                    'content' => 'Sólo se puede corregir una biografía rechazada.',
                ]);
            }

            $lockedProfile = ModelProfile::query()->whereKey($profile->getKey())->lockForUpdate()->firstOrFail();
            if ($lockedProfile->bios()->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages([
                    'content' => 'Ya existe una biografía pendiente de aprobación.',
                ]);
            }

            return $lockedProfile->bios()->create([
                'content' => $content,
                'status' => 'pending',
            ]);
        });
    }

    public function approve(ModelProfileBio $bio, User $admin): ModelProfileBio
    {
        $this->assertAdmin($admin);

        return DB::transaction(function () use ($bio, $admin): ModelProfileBio {
            $lockedBio = ModelProfileBio::query()->whereKey($bio->getKey())->lockForUpdate()->firstOrFail();
            if ($lockedBio->status !== 'pending') {
                throw ValidationException::withMessages(['bio' => 'Sólo se puede aprobar una biografía pendiente.']);
            }

            $profile = ModelProfile::query()->whereKey($lockedBio->model_profile_id)->lockForUpdate()->firstOrFail();
            $now = now();
            $profile->bios()
                ->where('status', 'pending')
                ->whereKeyNot($lockedBio->getKey())
                ->update([
                    'status' => 'rejected',
                    'reviewed_at' => $now,
                    'reviewed_by' => $admin->getKey(),
                    'rejection_reason' => 'Reemplazada por otra versión aprobada.',
                ]);

            $lockedBio->forceFill([
                'status' => 'approved',
                'reviewed_at' => $now,
                'reviewed_by' => $admin->getKey(),
                'rejection_reason' => null,
            ])->save();
            $profile->forceFill(['current_bio_id' => $lockedBio->getKey()])->save();

            return $lockedBio->refresh();
        });
    }

    public function reject(ModelProfileBio $bio, User $admin, string $reason): ModelProfileBio
    {
        $this->assertAdmin($admin);
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'El motivo del rechazo es obligatorio.']);
        }

        return DB::transaction(function () use ($bio, $admin, $reason): ModelProfileBio {
            $lockedBio = ModelProfileBio::query()->whereKey($bio->getKey())->lockForUpdate()->firstOrFail();
            if ($lockedBio->status !== 'pending') {
                throw ValidationException::withMessages(['bio' => 'Sólo se puede rechazar una biografía pendiente.']);
            }

            $lockedBio->forceFill([
                'status' => 'rejected',
                'reviewed_at' => now(),
                'reviewed_by' => $admin->getKey(),
                'rejection_reason' => $reason,
            ])->save();

            return $lockedBio->refresh();
        });
    }

    private function assertOwner(ModelProfile $profile, User $owner): void
    {
        if ((int) $profile->user_id !== (int) $owner->getKey()) {
            throw ValidationException::withMessages(['profile' => 'No podés modificar este perfil.']);
        }
    }

    private function assertAdmin(User $user): void
    {
        if (! $user->isVerifiedAdmin()) {
            throw ValidationException::withMessages(['bio' => 'No tenés autorización para moderar biografías.']);
        }
    }
}
