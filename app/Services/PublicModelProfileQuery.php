<?php

namespace App\Services;

use App\Enums\ModelPhotoVersionStatus;
use App\Models\Locality;
use App\Models\ModelPhoto;
use App\Models\ModelPhotoVersion;
use App\Models\ModelProfile;
use App\Models\ModelProfileSlug;
use App\Models\Province;
use App\ViewModels\PublicModelProfileViewModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class PublicModelProfileQuery
{
    public function __construct(
        private readonly PublicModelProfileVisibility $visibility,
        private readonly PublicModelPhotoService $photos,
        private readonly ModelPhotoStorage $storage,
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

    /**
     * Perfil + similares en una sola pasada: evita repetir la resolución del
     * slug y mantiene un conteo de consultas constante (las cargas de
     * similares son consultas explícitas que siempre se ejecutan, incluso
     * con resultados vacíos, para no variar según fotos/servicios del perfil).
     *
     * @return array{profile: ?PublicModelProfileViewModel, similar: list<array{name:string, url:string, photoUrl:string, photoAlt:string, location:string, available:bool}>}
     */
    public function findWithSimilar(string $slug, int $limit = 6): array
    {
        $profile = $this->findBySlug($slug);

        if (! $profile) {
            return ['profile' => null, 'similar' => []];
        }

        $reservation = ModelProfileSlug::query()->select(['model_profile_id'])->where('slug', $slug)->first();
        $excludeId = $reservation?->model_profile_id;
        if ($excludeId === null) {
            return ['profile' => $profile, 'similar' => []];
        }

        return ['profile' => $profile, 'similar' => $this->similarCards($excludeId, $limit)];
    }

    /**
     * @return list<array{name:string, url:string, photoUrl:string, photoAlt:string, location:string, available:bool}>
     */
    private function similarCards(int $excludeId, int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }

        $current = ModelProfile::query()->select(['id', 'province_id'])->whereKey($excludeId)->first();
        $provinceId = $current?->province_id;

        // Sin with(): las cargas eager se omiten con padres vacíos y el conteo
        // de queries variaría. Estas 5 consultas siempre se ejecutan.
        $rows = $this->visibility->candidates()
            ->where('model_profiles.id', '!=', $excludeId)
            ->when($provinceId !== null, fn ($query) => $query->orderByRaw(
                '(model_profiles.province_id = '.(int) $provinceId.') DESC'
            ))
            ->orderByDesc('model_profiles.id')
            ->limit($limit * 2)
            ->get();

        $provinceNames = Province::query()->select(['id', 'name'])
            ->whereIn('id', $rows->pluck('province_id')->filter()->unique()->values()->all())
            ->get()->keyBy('id');
        $localities = Locality::query()->select(['id', 'province_id', 'name'])
            ->whereIn('id', $rows->pluck('locality_id')->filter()->unique()->values()->all())
            ->get()->keyBy('id');
        $photos = ModelPhoto::query()->select(['id', 'model_profile_id', 'current_version_id', 'is_primary'])
            ->whereIn('model_profile_id', $rows->pluck('id')->all())
            ->where('is_primary', true)
            ->whereNotNull('current_version_id')
            ->get()->groupBy('model_profile_id');
        $versions = ModelPhotoVersion::query()->select(explode(',', PublicModelPhotoService::VERSION_COLUMNS))
            ->whereIn('id', $photos->flatten()->pluck('current_version_id')->filter()->unique()->values()->all())
            ->get()->keyBy('id');

        $cards = [];
        foreach ($rows as $row) {
            if (count($cards) >= $limit) {
                break;
            }
            $primaries = $photos->get($row->id, collect());
            if ($primaries->count() !== 1) {
                continue;
            }
            $photo = $primaries->first();
            $version = $versions->get($photo->current_version_id);
            if (! $version || $version->model_photo_id !== $photo->id
                || $version->status !== ModelPhotoVersionStatus::Approved
                || $version->public_watermarked_at === null
                || ! Str::isUuid($version->public_token ?? '')) {
                continue;
            }
            $stream = $this->storage->openPublicStream($version->public_path ?? '');
            if (! is_resource($stream)) {
                continue;
            }
            fclose($stream);

            $locality = $localities->get($row->locality_id);
            $location = ($locality && $locality->province_id === $row->province_id ? $locality->name : null)
                ?? $provinceNames->get($row->province_id)?->name ?? '';
            $cards[] = [
                'name' => $row->stage_name,
                'url' => route('public.models.show', ['slug' => $row->slug]),
                'photoUrl' => route('public.models.photos.show', ['publicToken' => $version->public_token]),
                'photoAlt' => 'Foto de '.$row->stage_name,
                'location' => $location,
                'available' => $row->availability_status === 'available',
            ];
        }

        return $cards;
    }
}
