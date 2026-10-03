<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Relations\HasMany;

class PublicModelHomepageProfiles
{
    public function __construct(private readonly PublicModelProfileVisibility $visibility) {}

    /** @return list<array{name:string, url:string, photoUrl:string, photoAlt:string}> */
    public function cards(int $limit = 6): array
    {
        $profiles = $this->visibility->candidates()
            ->with(['currentApprovedPhotos' => fn (HasMany $photos) => $photos
                ->select(['id', 'model_profile_id', 'current_version_id', 'is_primary'])
                ->where('is_primary', true)
                ->with('currentVersion:'.PublicModelPhotoService::VERSION_COLUMNS),
            ])
            ->orderByDesc('model_profiles.id')
            ->limit(max(0, $limit))
            ->get();

        $cards = [];
        foreach ($profiles as $profile) {
            if (! $this->visibility->publiclyVisible($profile)) {
                continue;
            }

            $photo = $profile->currentApprovedPhotos->first();
            $version = $photo?->currentVersion;
            if (! $photo || ! $version) {
                continue;
            }

            $cards[] = [
                'name' => $profile->stage_name,
                'url' => route('public.models.show', ['slug' => $profile->slug]),
                'photoUrl' => route('public.models.photos.show', ['publicToken' => $version->public_token]),
                'photoAlt' => 'Foto de '.$profile->stage_name,
            ];
        }

        return $cards;
    }
}
