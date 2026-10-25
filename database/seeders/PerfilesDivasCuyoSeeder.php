<?php

namespace Database\Seeders;

use App\Models\Locality;
use App\Models\Province;
use App\Models\PublicationType;
use App\Models\Service;
use App\Models\User;
use App\Services\ModelProfileSlugService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Importa los perfiles reales de `seeders/data/perfiles_divas_cuyo.json`.
 *
 * Re-ejecutable: usa firstOrCreate/updateOrCreate en cada paso.
 * No crea fotografías: los perfiles quedan completos pero no visibles en
 * público hasta subir la foto principal (ver PublicModelProfileVisibility).
 */
class PerfilesDivasCuyoSeeder extends Seeder
{
    private const SOURCE = 'seeders/data/perfiles_divas_cuyo.json';

    private const TYPE_MAP = ['creadora' => 'virtual', 'escort' => 'encounters'];

    /** Nombres del JSON que corresponden a otro servicio existente. */
    private const SERVICE_ALIASES = [
        'video llamada' => 'videollamada',
        'streptease' => 'striptease',
        'masaje con final feliz' => 'masajes-con-final-feliz',
    ];

    /** @var array<string, int> */
    private array $usedStageNames = [];

    public function run(): void
    {
        $path = database_path(self::SOURCE);
        if (! is_file($path)) {
            $this->command?->warn('Sin fuente: '.self::SOURCE);
            return;
        }

        /** @var array{perfiles?: list<array<string, mixed>>} $data */
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        foreach ($data['perfiles'] ?? [] as $row) {
            $this->seedRow($row);
        }
    }

    /** @param array<string, mixed> $row */
    private function seedRow(array $row): void
    {
        $typeSlug = self::TYPE_MAP[strtolower(trim((string) ($row['Tipo'] ?? '')))] ?? null;
        if ($typeSlug === null) {
            $this->command?->warn('Tipo desconocido: '.($row['Nombre'] ?? '?'));
            return;
        }

        $stageName = $this->uniqueStageName(trim((string) ($row['Título del perfil'] ?? $row['Nombre'] ?? '')));
        $email = Str::slug($stageName).'@divascuyo.test';
        $phone = $this->cleanPhone($row['WhatsApp'] ?? $row['Teléfono'] ?? null) ?? 'No publicado';
        [$province, $locality, $zone] = $this->resolveLocation($row);
        // users.location es NOT NULL: etiqueta genérica, nunca dirección exacta.
        $areaLabel = $locality?->name ?? $province?->name ?? 'Cuyo';

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $stageName,
                'password' => Hash::make(Str::random(32)),
                'whatsapp' => $phone,
                'location' => $areaLabel,
                'is_published' => true,
            ],
        );
        $user->forceFill([
            'email_verified_at' => $user->email_verified_at ?? now(),
            'is_published' => true,
            'is_admin' => false,
        ])->save();

        $publicationType = PublicationType::query()->where('slug', $typeSlug)->firstOrFail();
        $profile = $user->modelProfile()->firstOrNew();
        $profile->forceFill([
            'name' => $stageName,
            'whatsapp' => $phone,
            'location' => $areaLabel,
            'public_age' => is_numeric($row['Edad'] ?? null) ? (int) $row['Edad'] : null,
            'show_age' => is_numeric($row['Edad'] ?? null),
            'height_cm' => $this->parseHeight($row['Altura'] ?? null),
            'weight_kg' => null,
            'measurements' => $this->cleanText($row['Medidas'] ?? null),
            'eye_color' => $this->cleanText($row['Ojos'] ?? null),
            'hair_color' => $this->cleanText($row['Cabello'] ?? null),
            'skin_color' => $this->cleanText($row['Color de piel'] ?? null),
            'body_type' => $this->cleanBodyType($row['Cuerpo'] ?? null),
            'nationality' => $this->cleanText($row['Nacionalidad'] ?? null),
            'availability_status' => 'available',
            'schedule_availability' => $this->parseSchedule($row['Disponibilidad'] ?? null),
            'depilation' => $this->parseDepilation($row['Depilación'] ?? null),
            'identity_status' => 'approved',
            'review_status' => 'approved',
            'is_published' => true,
            'publication_type_id' => $publicationType->id,
            'province_id' => $province?->id,
            'locality_id' => $locality?->id,
            'approximate_location_text' => $zone,
        ])->save();

        $profile = app(ModelProfileSlugService::class)->rename($profile, $stageName);
        $bio = $profile->bios()->firstOrCreate(
            ['content' => $this->buildBio($row)],
            ['status' => 'approved'],
        );
        $bio->forceFill([
            'status' => 'approved',
            'reviewed_at' => $bio->reviewed_at ?? now(),
        ])->save();
        $profile->forceFill(['current_bio_id' => $bio->id])->save();

        $profile->services()->sync($this->serviceIds($row));
    }

    private function uniqueStageName(string $base): string
    {
        $count = $this->usedStageNames[$base] ?? 0;
        $this->usedStageNames[$base] = $count + 1;

        return $count === 0 ? $base : $base.' '.($count + 1);
    }

    private function cleanPhone(mixed $value): ?string
    {
        $phone = trim((string) ($value ?? ''));
        if ($phone === '' || strcasecmp($phone, 'none') === 0 || stripos($phone, 'no publicado') !== false) {
            return null;
        }

        return $phone;
    }

    private function cleanText(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '' || strcasecmp($text, 'none') === 0) {
            return null;
        }

        return $text;
    }

    private function cleanBodyType(mixed $value): ?string
    {
        $text = $this->cleanText($value);
        if ($text !== null && strcasecmp($text, 'fitnes') === 0) {
            return 'Fitness';
        }

        return $text;
    }

    private function parseHeight(mixed $value): ?int
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '' || ! is_numeric(str_replace(',', '.', $text))) {
            return null;
        }

        return (int) round((float) str_replace(',', '.', $text) * 100);
    }

    private function parseSchedule(mixed $value): ?string
    {
        return match (strtolower(trim((string) ($value ?? '')))) {
            'full time' => 'full_time',
            'part time' => 'part_time',
            default => null,
        };
    }

    private function parseDepilation(mixed $value): ?string
    {
        return match (strtolower(trim((string) ($value ?? '')))) {
            'full' => 'full',
            'partial', 'parcial' => 'partial',
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $row
     * @return array{0: ?Province, 1: ?Locality, 2: ?string}
     */
    private function resolveLocation(array $row): array
    {
        $place = trim((string) ($row['Ubicación'] ?? ''));
        if ($place === '' || strcasecmp($place, 'consultar') === 0 || strcasecmp($place, 'none') === 0) {
            return [null, null, null];
        }

        $provinceName = stripos($place, 'san luis') !== false ? 'San Luis' : 'Mendoza';
        $province = Province::query()->firstOrCreate(
            ['slug' => Str::slug($provinceName)],
            ['name' => $provinceName],
        );

        $zona = (string) ($row['Zona / dirección'] ?? '');
        $localityName = match (true) {
            stripos($place, 'godoy cruz') !== false || stripos($zona, 'godoy cruz') !== false => 'Godoy Cruz',
            stripos($zona, 'san luis') !== false => 'Ciudad de San Luis',
            $provinceName === 'San Luis' => 'Ciudad de San Luis',
            default => 'Ciudad de Mendoza',
        };
        $locality = Locality::query()->firstOrCreate(
            ['province_id' => $province->id, 'slug' => Str::slug($localityName)],
            ['name' => $localityName],
        );

        // La zona trae dirección exacta y approximate_location_text es público:
        // se conserva solo el tramo sin numeración.
        $zone = trim((string) preg_replace('/^consultar dirección exacta\s*\|\s*/i', '', $zona));
        if ($zone === '' || strcasecmp($zone, 'none') === 0) {
            $zone = null;
        }

        return [$province, $locality, $zone];
    }

    /** @param array<string, mixed> $row */
    private function buildBio(array $row): string
    {
        $bio = trim((string) ($row['Descripción'] ?? ''));
        $extras = [];
        foreach ([
            'Idiomas' => $this->cleanText($row['Idiomas'] ?? null),
            'Busto' => $this->cleanText($row['Busto'] ?? null),
            'Cola' => $this->cleanText($row['Cola'] ?? null),
            'Fuma' => $this->cleanText($row['Fuma'] ?? null),
        ] as $label => $value) {
            if ($value !== null) {
                $extras[] = $label.': '.$value;
            }
        }
        $otros = $this->cleanText($row['Otros datos'] ?? null);
        if ($otros !== null) {
            $extras[] = $otros;
        }
        if ($extras !== []) {
            $bio .= "\n\n".implode("\n", $extras);
        }

        return $bio;
    }

    /** @param array<string, mixed> $row @return list<int> */
    private function serviceIds(array $row): array
    {
        $details = $row['Servicios detallados'] ?? [];
        if (! is_array($details)) {
            return [];
        }
        $slugs = [];
        foreach ($details as $name => $mark) {
            if ($mark !== '✓') {
                continue;
            }
            $mapped = self::SERVICE_ALIASES[strtolower($name)] ?? Str::slug($name);
            $slugs[] = $mapped;
        }

        return Service::query()->whereIn('slug', array_unique($slugs))->where('is_active', true)->pluck('id')->all();
    }
}
