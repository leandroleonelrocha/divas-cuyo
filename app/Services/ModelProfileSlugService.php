<?php

namespace App\Services;

use App\Models\ModelProfile;
use App\Models\ModelProfileSlug;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ModelProfileSlugService
{
    public function base(string $name): ?string
    {
        if (trim($name) === '') {
            return null;
        }

        $base = preg_replace('/[^a-z0-9-]/', '', Str::slug($name));
        $base = $base === '' ? 'modelo' : $base;
        if (ctype_digit($base)) {
            $base = 'modelo-'.$base;
        }

        return rtrim(substr($base, 0, 160), '-');
    }

    public function rename(ModelProfile $profile, string $stageName): ModelProfile
    {
        return DB::transaction(function () use ($profile, $stageName): ModelProfile {
            $locked = ModelProfile::query()->lockForUpdate()->findOrFail($profile->getKey());
            if ($locked->slug !== null && ! $this->reserve($locked->slug, $locked->id)) {
                throw ValidationException::withMessages(['stage_name' => 'No se pudo conservar el enlace público.']);
            }

            $base = $this->base($stageName);
            if ($base === null) {
                $slug = null;
            } elseif ($locked->slug !== null && $this->base($locked->stage_name ?? '') === $base) {
                $slug = $locked->slug;
            } else {
                $slug = $this->allocate($base, $locked->id);
            }

            $locked->forceFill(['stage_name' => $stageName, 'slug' => $slug])->save();

            return $locked;
        }, 3);
    }

    private function allocate(string $base, int $profileId): string
    {
        for ($number = 1; $number <= 10000; $number++) {
            $suffix = $number === 1 ? '' : '-'.$number;
            $candidate = rtrim(substr($base, 0, 160 - strlen($suffix)), '-').$suffix;
            // Also protect existing, not-yet-backfilled US1 assignments.
            if (ModelProfile::query()->where('slug', $candidate)->whereKeyNot($profileId)->exists()) {
                continue;
            }
            if ($this->reserve($candidate, $profileId)) {
                return $candidate;
            }
        }

        throw ValidationException::withMessages(['stage_name' => 'No se pudo asignar el enlace público. Intentá nuevamente.']);
    }

    private function reserve(string $slug, int $profileId): bool
    {
        $reservation = ModelProfileSlug::query()->where('slug', $slug)->lockForUpdate()->first();
        if ($reservation) {
            return $reservation->model_profile_id === $profileId;
        }

        try {
            (new ModelProfileSlug)->forceFill(['slug' => $slug, 'model_profile_id' => $profileId])->save();

            return true;
        } catch (UniqueConstraintViolationException $exception) {
            if (! str_contains($exception->getMessage(), 'model_profile_slugs_slug_unique')
                && ! str_contains($exception->getMessage(), 'model_profile_slugs.slug')) {
                throw $exception;
            }

            return ModelProfileSlug::query()->where('slug', $slug)->lockForUpdate()->first()?->model_profile_id === $profileId;
        }
    }
}
