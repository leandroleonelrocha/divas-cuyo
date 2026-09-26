<?php

namespace App\Services;

use App\Models\ModelProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use LogicException;

class ModelProfileModerationService
{
    public function approve(ModelProfile $profile, User $reviewer): ModelProfile
    {
        return $this->review($profile, 'approved', $reviewer, ['pending', 'rejected']);
    }

    public function reject(ModelProfile $profile, User $reviewer): ModelProfile
    {
        return $this->review($profile, 'rejected', $reviewer, ['pending', 'approved']);
    }

    public function publish(ModelProfile $profile): ModelProfile
    {
        return DB::transaction(function () use ($profile): ModelProfile {
            $lockedProfile = $this->lockProfile($profile);

            if ($lockedProfile->identity_status !== 'approved') {
                throw new LogicException('No se puede publicar el perfil hasta que la identidad y el perfil estén aprobados.');
            }

            if ($lockedProfile->review_status !== 'approved' || $lockedProfile->is_published) {
                throw new LogicException('Sólo se puede publicar un perfil aprobado y no publicado.');
            }

            $lockedProfile->forceFill(['is_published' => true])->save();

            return $profile->refresh();
        });
    }

    public function unpublish(ModelProfile $profile): ModelProfile
    {
        return DB::transaction(function () use ($profile): ModelProfile {
            $lockedProfile = $this->lockProfile($profile);

            if (! $lockedProfile->is_published) {
                throw new LogicException('Sólo se puede despublicar un perfil publicado.');
            }

            $lockedProfile->forceFill(['is_published' => false])->save();

            return $profile->refresh();
        });
    }

    /**
     * @param  array<int, string>  $allowedStatuses
     */
    private function review(ModelProfile $profile, string $status, User $reviewer, array $allowedStatuses): ModelProfile
    {
        return DB::transaction(function () use ($profile, $status, $reviewer, $allowedStatuses): ModelProfile {
            $lockedProfile = $this->lockProfile($profile);

            if (! in_array($lockedProfile->review_status, $allowedStatuses, true)) {
                throw new LogicException('La transición de revisión solicitada no está permitida.');
            }

            if ($status === 'approved' && $lockedProfile->identity_status !== 'approved') {
                throw new LogicException('No se puede aprobar el perfil hasta que la identidad esté validada.');
            }

            $lockedProfile->forceFill([
                'review_status' => $status,
                'is_published' => $status === 'rejected' ? false : $lockedProfile->is_published,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->getKey(),
            ])->save();

            return $profile->refresh();
        });
    }

    private function lockProfile(ModelProfile $profile): ModelProfile
    {
        $lockedProfile = ModelProfile::query()->lockForUpdate()->find($profile->getKey());

        if (! $lockedProfile) {
            throw (new ModelNotFoundException)->setModel(ModelProfile::class, [$profile->getKey()]);
        }

        return $lockedProfile;
    }
}
