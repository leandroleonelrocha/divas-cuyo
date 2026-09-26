<?php

namespace App\Services;

use App\Models\ModelProfile;
use App\Models\ModelProfilePhysicalRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

class ModelProfilePhysicalModerationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(ModelProfile $profile, User $submitter, array $data): ModelProfilePhysicalRevision
    {
        return DB::transaction(function () use ($profile, $submitter, $data): ModelProfilePhysicalRevision {
            $lockedProfile = ModelProfile::query()->lockForUpdate()->findOrFail($profile->getKey());

            if ($lockedProfile->physicalRevisions()->where('status', 'pending')->exists()) {
                throw new LogicException('Ya existe una modificación física pendiente de revisión.');
            }

            return $lockedProfile->physicalRevisions()->create([
                ...$data,
                'status' => 'pending',
                'submitted_by' => $submitter->getKey(),
                'reviewed_at' => null,
                'reviewed_by' => null,
                'rejection_reason' => null,
            ]);
        });
    }

    public function approve(ModelProfilePhysicalRevision $revision, User $reviewer): ModelProfilePhysicalRevision
    {
        return DB::transaction(function () use ($revision, $reviewer): ModelProfilePhysicalRevision {
            $lockedRevision = ModelProfilePhysicalRevision::query()->lockForUpdate()->findOrFail($revision->getKey());
            $profile = ModelProfile::query()->lockForUpdate()->findOrFail($lockedRevision->model_profile_id);

            $this->ensurePending($lockedRevision);

            $profile->forceFill($this->snapshot($lockedRevision))->save();
            $lockedRevision->forceFill([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->getKey(),
                'rejection_reason' => null,
            ])->save();

            return $lockedRevision->refresh();
        });
    }

    public function reject(ModelProfilePhysicalRevision $revision, User $reviewer, string $reason): ModelProfilePhysicalRevision
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new LogicException('Debés indicar un motivo para rechazar la modificación física.');
        }

        return DB::transaction(function () use ($revision, $reviewer, $reason): ModelProfilePhysicalRevision {
            $lockedRevision = ModelProfilePhysicalRevision::query()->lockForUpdate()->findOrFail($revision->getKey());
            $this->ensurePending($lockedRevision);

            $lockedRevision->forceFill([
                'status' => 'rejected',
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->getKey(),
                'rejection_reason' => $reason,
            ])->save();

            return $lockedRevision->refresh();
        });
    }

    private function ensurePending(ModelProfilePhysicalRevision $revision): void
    {
        if ($revision->status !== 'pending') {
            throw new LogicException('Sólo se puede revisar una modificación física pendiente.');
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(ModelProfilePhysicalRevision $revision): array
    {
        return [
            'height_cm' => $revision->height_cm,
            'weight_kg' => $revision->weight_kg,
            'measurements' => $revision->measurements,
            'eye_color' => $revision->eye_color,
            'hair_color' => $revision->hair_color,
            'skin_color' => $revision->skin_color,
            'body_type' => $revision->body_type,
            'nationality' => $revision->nationality,
        ];
    }
}
