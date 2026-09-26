<?php

namespace App\Services;

use App\Models\ModelProfile;
use App\Models\PublicationType;
use App\Models\Service;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublicationTypeChangeService
{
    public function change(
        ModelProfile $profile,
        PublicationType $publicationType,
        ?Authenticatable $actor = null,
        string $source = 'model',
        ?string $reason = null,
        bool $confirmInPersonRemoval = false,
    ): ModelProfile {
        if (! in_array($source, ['model', 'admin', 'system'], true)) {
            throw ValidationException::withMessages(['source' => 'El origen del cambio no es válido.']);
        }

        if (! $publicationType->is_active) {
            throw ValidationException::withMessages(['publication_type_id' => 'El tipo de publicación no está activo.']);
        }

        return DB::transaction(function () use ($profile, $publicationType, $actor, $source, $reason, $confirmInPersonRemoval): ModelProfile {
            $lockedProfile = ModelProfile::query()
                ->whereKey($profile->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedProfile) {
                throw (new ModelNotFoundException)->setModel(ModelProfile::class, [$profile->getKey()]);
            }

            if ((int) $lockedProfile->publication_type_id === (int) $publicationType->getKey()) {
                return $lockedProfile->load('publicationType');
            }

            $inPersonServiceIds = $lockedProfile->services()
                ->where('services.service_type', 'in_person')
                ->pluck('services.id');

            if ($publicationType->slug === 'virtual' && $inPersonServiceIds->isNotEmpty()) {
                if (! $confirmInPersonRemoval) {
                    throw ValidationException::withMessages([
                        'confirm_in_person_removal' => 'Confirmá la desactivación de los servicios presenciales antes de pasar a Solo Virtual.',
                    ]);
                }

                $lockedProfile->services()->detach($inPersonServiceIds->all());
            }

            $fromTypeId = $lockedProfile->publication_type_id;
            $lockedProfile->forceFill(['publication_type_id' => $publicationType->getKey()])->save();

            $lockedProfile->publicationTypeHistory()->create([
                'from_publication_type_id' => $fromTypeId,
                'to_publication_type_id' => $publicationType->getKey(),
                'changed_by_user_id' => $actor?->getAuthIdentifier(),
                'source' => $source,
                'reason' => $reason,
                'changed_at' => now(),
            ]);

            return $lockedProfile->refresh()->load('publicationType');
        });
    }

    /**
     * @param  array<int, int|string>  $serviceIds
     */
    public function syncServices(ModelProfile $profile, array $serviceIds): ModelProfile
    {
        return DB::transaction(function () use ($profile, $serviceIds): ModelProfile {
            $lockedProfile = ModelProfile::query()
                ->whereKey($profile->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $ids = collect($serviceIds)->map(fn ($id): int => (int) $id)->unique()->values();
            $services = Service::query()->whereIn('id', $ids)->where('is_active', true)->get();

            if ($services->count() !== $ids->count()) {
                throw ValidationException::withMessages(['service_ids' => 'La selección contiene servicios inexistentes o inactivos.']);
            }

            if ($lockedProfile->publicationType?->slug === 'virtual' && $services->contains('service_type', 'in_person')) {
                throw ValidationException::withMessages(['service_ids' => 'Solo Virtual no permite servicios presenciales.']);
            }

            $lockedProfile->services()->sync($ids->all());

            return $lockedProfile->refresh()->load(['publicationType', 'services']);
        });
    }
}
