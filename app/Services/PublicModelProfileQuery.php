<?php

namespace App\Services;

use App\Models\ModelProfile;
use App\Models\ModelProfileSlug;
use App\ViewModels\PublicModelProfileViewModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Gate;

class PublicModelProfileQuery
{
    public function __construct(
        private readonly PublicModelProfileVisibility $visibility,
        private readonly PublicModelPhotoService $photos,
    ) {}

    public function findBySlug(string $slug): ?PublicModelProfileViewModel
    {
        if (! $this->validSlug($slug)) {
            return null;
        }

        $reservation = ModelProfileSlug::query()->select(['model_profile_id'])->where('slug', $slug)->first();
        if (! $reservation || $reservation->model_profile_id === null) {
            return null;
        }

        $profile = $this->visibility->candidates()->whereKey($reservation->model_profile_id)
            ->addSelect(['height_cm', 'weight_kg', 'measurements', 'eye_color', 'hair_color',
                'skin_color', 'body_type', 'nationality', 'show_age', 'public_age', 'current_bio_id', 'publication_type_id'])
            ->with(['publicationType:id,slug',
                'services' => fn (BelongsToMany $services) => $services
                    ->where('services.is_active', true)
                    ->whereIn('services.service_type', ['virtual', 'in_person'])
                    ->select(['services.id', 'services.name', 'services.service_type', 'services.is_active', 'services.sort_order'])
                    ->orderBy('services.sort_order')->orderBy('services.id'),
                'province:id,name', 'locality:id,province_id,name',
                'currentBio' => fn (BelongsTo $bio) => $bio
                    ->select(['id', 'model_profile_id', 'content', 'status'])->where('status', 'approved')
                    ->where('model_profile_id', $reservation->model_profile_id),
                'currentApprovedPhotos' => fn (HasMany $photos) => $photos
                    ->select(['id', 'model_profile_id', 'current_version_id', 'position', 'is_primary'])
                    ->orderBy('id')->with('currentVersion:'.PublicModelPhotoService::VERSION_COLUMNS),
            ])->first();

        if (! $profile || ! $this->validSlug($profile->slug) || Gate::denies('viewPublicModelProfile', $profile)) {
            return null;
        }

        $location = [$profile->province?->name];
        if ($profile->province && $profile->locality?->province_id === $profile->province_id) {
            $location[] = $profile->locality->name;
        }
        $location[] = $profile->approximate_location_text;
        $location = array_values(array_filter($location, fn (?string $value) => $value !== null && trim($value) !== ''));
        $publicationTypeLabel = $this->publicationTypeLabel($profile->publicationType?->slug);
        $descriptionParts = [$profile->stage_name];
        if ($publicationTypeLabel !== null) {
            $descriptionParts[] = $publicationTypeLabel;
        }
        $description = 'Perfil público de '.implode(' · ', [...$descriptionParts, ...$location]).'.';

        $gallery = $this->photos->gallery($profile);
        if ($gallery['primary'] === null) {
            return null;
        }

        return new PublicModelProfileViewModel(
            stageName: $profile->stage_name,
            canonicalUrl: route('public.models.show', ['slug' => $profile->slug]),
            verifiedLabel: 'Modelo verificada por Divas Cuyo',
            availabilityLabel: $profile->availability_status === 'available' ? 'Disponible' : 'No disponible',
            location: $location,
            primaryPhoto: $gallery['primary'],
            photos: $gallery['photos'],
            characteristics: $this->characteristics($profile),
            bio: $profile->currentBio?->model_profile_id === $profile->id
                && trim($profile->currentBio->content) !== '' ? $profile->currentBio->content : null,
            publicationTypeLabel: $publicationTypeLabel,
            serviceGroups: $this->serviceGroups($profile),
            title: $profile->stage_name.' | Divas Cuyo',
            description: $description,
        );
    }

    private function publicationTypeLabel(?string $slug): ?string
    {
        return match ($slug) {
            'virtual' => 'Solo Virtual',
            'encounters' => 'Encuentros',
            default => null,
        };
    }

    /** @return array<string, list<string>> */
    private function serviceGroups(ModelProfile $profile): array
    {
        $allowedTypes = match ($profile->publicationType?->slug) {
            'virtual' => ['virtual'],
            'encounters' => ['virtual', 'in_person'],
            default => [],
        };
        if ($allowedTypes === []) {
            return [];
        }

        $groups = ['Servicios virtuales' => [], 'Servicios presenciales' => []];
        foreach ($profile->services as $service) {
            $name = trim($service->name);
            if (! $service->is_active || ! in_array($service->service_type, $allowedTypes, true) || $name === '') {
                continue;
            }

            $group = $service->service_type === 'virtual' ? 'Servicios virtuales' : 'Servicios presenciales';
            $groups[$group][] = $name;
        }

        return array_filter($groups, static fn (array $services): bool => $services !== []);
    }

    /** @return array<string, string> */
    private function characteristics(ModelProfile $profile): array
    {
        $values = [];
        if ($profile->show_age && $profile->public_age !== null) {
            $values['Edad'] = $profile->public_age.' años';
        }
        if ($profile->height_cm !== null) {
            $values['Altura'] = number_format($profile->height_cm / 100, 2, ',', '').' m';
        }
        if ($profile->weight_kg !== null) {
            $values['Peso'] = rtrim(rtrim(number_format((float) $profile->weight_kg, 2, ',', ''), '0'), ',').' kg';
        }
        foreach (['measurements' => 'Medidas', 'eye_color' => 'Ojos', 'hair_color' => 'Cabello',
            'skin_color' => 'Piel', 'body_type' => 'Tipo de cuerpo', 'nationality' => 'Nacionalidad'] as $field => $label) {
            if ($profile->$field !== null && trim($profile->$field) !== '') {
                $values[$label] = trim($profile->$field);
            }
        }

        return $values;
    }

    private function validSlug(string $slug): bool
    {
        return strlen($slug) <= 160 && ! ctype_digit($slug)
            && preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slug) === 1;
    }
}
