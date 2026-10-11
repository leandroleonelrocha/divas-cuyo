<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Relations\HasMany;

class PublicModelHomepageProfiles
{
    public function __construct(private readonly PublicModelProfileVisibility $visibility) {}

    /** @return list<array{name:string, url:string, photoUrl:string, photoAlt:string, province:string, provinceName:string, locality:string, location:string, available:bool, service:string}> */
    public function cards(?int $limit = 6): array
    {
        return $this->fetchCards($limit);
    }

    /**
     * Todas las modelos visibles agrupadas por provincia (ordenadas por nombre de provincia).
     *
     * @return list<array{slug:string, name:string, profiles:list<array{name:string, url:string, photoUrl:string, photoAlt:string, province:string, provinceName:string, locality:string, location:string, available:bool, service:string}>}>
     */
    public function groupedByProvince(): array
    {
        $cards = $this->fetchCards(null);
        $groups = [];
        foreach ($cards as $card) {
            $slug = $card['province'] !== '' ? $card['province'] : 'otras';
            if (! isset($groups[$slug])) {
                $groups[$slug] = [
                    'slug' => $slug,
                    'name' => $card['provinceName'] !== '' ? $card['provinceName'] : 'Otras localidades',
                    'profiles' => [],
                ];
            }
            $groups[$slug]['profiles'][] = $card;
        }

        usort($groups, fn (array $a, array $b) => $a['slug'] === 'otras' ? 1 : ($b['slug'] === 'otras' ? -1 : strcmp($a['name'], $b['name'])));

        return array_values($groups);
    }

    /** @return list<array{name:string, url:string, photoUrl:string, photoAlt:string, province:string, provinceName:string, locality:string, location:string, available:bool, service:string}> */
    private function fetchCards(?int $limit): array
    {
        $query = $this->visibility->candidates()
            ->with(['province:id,name,slug', 'locality:id,name,slug', 'publicationType:id,slug,allows_in_person_services', 'currentApprovedPhotos' => fn (HasMany $photos) => $photos
                ->select(['id', 'model_profile_id', 'current_version_id', 'is_primary'])
                ->where('is_primary', true)
                ->with('currentVersion:'.PublicModelPhotoService::VERSION_COLUMNS),
            ])
            ->orderByDesc('model_profiles.id');

        if ($limit !== null) {
            $query->limit(max(0, $limit));
        }

        $profiles = $query->get();

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
                'province' => $profile->province?->slug ?? '',
                'provinceName' => $profile->province?->name ?? '',
                'locality' => $profile->locality?->slug ?? '',
                'location' => $profile->locality?->name ?? $profile->province?->name ?? 'Argentina',
                'available' => $profile->availability_status === 'available',
                'service' => ($profile->publicationType?->allows_in_person_services ?? true) ? 'escort' : 'virtual',
            ];
        }

        return $cards;
    }
}
