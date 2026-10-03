<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    // Local models and normalization keep this data migration independent of app services.
    private function profiles(): Model
    {
        return new class extends Model
        {
            protected $table = 'model_profiles';

            protected $guarded = [];
        };
    }

    private function reservations(): Model
    {
        return new class extends Model
        {
            protected $table = 'model_profile_slugs';

            protected $guarded = [];
        };
    }

    public function up(): void
    {
        // Reserve ALL preexisting values before allocating anything to older profile IDs.
        $this->profiles()->newQuery()->select(['id'])->whereNotNull('slug')->chunkById(100, function ($profiles): void {
            foreach ($profiles as $profile) {
                DB::transaction(function () use ($profile): void {
                    $locked = $this->profiles()->newQuery()->lockForUpdate()->find($profile->id, ['id', 'slug']);
                    if ($locked && $locked->slug !== null && ! $this->reserve($locked->slug, $locked->id)) {
                        throw new RuntimeException('Conflicting public slug reservation; no existing slug was overwritten.');
                    }
                }, 3);
            }
        });

        $this->profiles()->newQuery()->select(['id'])->whereNull('slug')->whereNotNull('stage_name')->chunkById(100, function ($profiles): void {
            foreach ($profiles as $profile) {
                DB::transaction(function () use ($profile): void {
                    $locked = $this->profiles()->newQuery()->lockForUpdate()->find($profile->id, ['id', 'slug', 'stage_name']);
                    if (! $locked || $locked->slug !== null || trim($locked->stage_name ?? '') === '') {
                        return;
                    }
                    $base = preg_replace('/[^a-z0-9-]/', '', Str::slug($locked->stage_name));
                    $base = $base === '' ? 'modelo' : $base;
                    if (ctype_digit($base)) {
                        $base = 'modelo-'.$base;
                    }
                    $base = rtrim(substr($base, 0, 160), '-');
                    for ($number = 1; $number <= 10000; $number++) {
                        $suffix = $number === 1 ? '' : '-'.$number;
                        $candidate = rtrim(substr($base, 0, 160 - strlen($suffix)), '-').$suffix;
                        if ($this->profiles()->newQuery()->where('slug', $candidate)->whereKeyNot($locked->id)->exists()) {
                            continue;
                        }
                        if ($this->reserve($candidate, $locked->id)) {
                            $locked->slug = $candidate;
                            $locked->save();

                            return;
                        }
                    }
                    throw new RuntimeException('Public slug allocation exhausted; rerun after resolving reservations.');
                }, 3);
            }
        });
    }

    private function reserve(string $slug, int $profileId): bool
    {
        $reservation = $this->reservations()->newQuery()->where('slug', $slug)->lockForUpdate()->first();
        if ($reservation) {
            return $reservation->model_profile_id === $profileId;
        }
        try {
            $this->reservations()->newQuery()->create(['slug' => $slug, 'model_profile_id' => $profileId]);

            return true;
        } catch (UniqueConstraintViolationException $exception) {
            if (! str_contains($exception->getMessage(), 'model_profile_slugs_slug_unique')
                && ! str_contains($exception->getMessage(), 'model_profile_slugs.slug')) {
                throw $exception;
            }

            return $this->reservations()->newQuery()->where('slug', $slug)->lockForUpdate()->first()?->model_profile_id === $profileId;
        }
    }

    public function down(): void
    {
        // Data backfill is intentionally non-destructive: published URLs may already be shared.
        // Schema rollback requires a reservation backup and disabling public routes first.
    }
};
