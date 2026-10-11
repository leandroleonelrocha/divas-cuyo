<?php

namespace Database\Seeders;

use App\Models\Locality;
use App\Models\Province;
use App\Models\PublicationType;
use App\Models\Service;
use App\Models\User;
use App\Services\ModelPhotoImageProcessor;
use App\Services\ModelPhotoStorage;
use App\Services\ModelProfileSlugService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestModelProfilesSeeder extends Seeder
{
    private const FIRST_NAMES = [
        'Valentina', 'Martina', 'Sofía', 'Catalina', 'Lucía', 'Emma',
        'Victoria', 'Renata', 'Antonella', 'Micaela', 'Florencia', 'Carolina',
        'Paula', 'Daniela', 'Fernanda', 'Julieta', 'Luciana', 'Agustina',
        'Carla', 'Marina', 'Noelia', 'Rocío', 'Tamara', 'Vanesa',
        'Ximena', 'Yamila', 'Zoe', 'Ariana', 'Brenda', 'Daiana',
    ];

    private const LAST_NAMES = [
        'Rossi', 'Bianchi', 'Ferrari', 'Romano', 'Conti', 'Ricci',
        'Marino', 'Greco', 'Bruno', 'Gallo', 'Serra', 'Riva',
        'Costa', 'Fontana', 'Lombardo', 'Vitale', 'Marchetti', 'Bellini',
        'Ferraro', 'Colombo', 'Sartori', 'Moretti', 'Barbieri', 'Rinaldi',
        'Gatti', 'Pellegrini', 'Bianco', 'Neri', 'Parisi', 'Villa',
    ];

    private const BIOS = [
        'Perfil de prueba. Me gusta la música, el cine y las buenas conversaciones.',
        'Perfil de prueba. Disfruto el aire libre, los viajes y conocer gente nueva.',
        'Perfil de prueba. Soy alegre, respetuosa y valoro la puntualidad.',
        'Perfil de prueba. Me apasionan la moda, la fotografía y el arte.',
        'Perfil de prueba. Busco encuentros con buena onda y total discreción.',
    ];

    private const PALETTE = [
        [178, 34, 64], [34, 120, 178], [46, 139, 87], [147, 112, 219],
        [210, 105, 30], [0, 139, 139], [199, 21, 133], [106, 90, 205],
    ];

    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $plan = $this->plan();
        $processor = app(ModelPhotoImageProcessor::class);
        $storage = app(ModelPhotoStorage::class);
        $adminId = User::query()->where('is_admin', true)->value('id');

        $created = 0;
        foreach ($plan as $index => $item) {
            $number = $index + 1;
            $this->seedProfile($item, $number, $processor, $storage, $adminId);
            $created++;
        }

        $this->command->info("Seeder de prueba: {$created} perfiles procesados.");
    }

    /**
     * 30 perfiles: 20 presenciales (encounters) + 10 virtuales,
     * 10 por provincia (Mendoza, San Juan, San Luis).
     *
     * @return list<array{province:string, locality:string, zone:string, type:string}>
     */
    private function plan(): array
    {
        $spots = [
            'Mendoza' => [
                ['Ciudad de Mendoza', 'Zona centro'], ['Godoy Cruz', 'Zona norte'],
                ['Guaymallén', 'Zona este'], ['Maipú', 'Zona sur'],
            ],
            'San Juan' => [
                ['Capital', 'Centro'], ['Rawson', 'Zona sur'], ['Rivadavia', 'Zona oeste'],
            ],
            'San Luis' => [
                ['Ciudad de San Luis', 'Centro'], ['Villa Mercedes', 'Zona norte'], ['Merlo', 'Zona este'],
            ],
        ];

        // Tipo por posición dentro de cada provincia (10): 7-7-6 presenciales, 3-3-4 virtuales.
        $typesByProvince = [
            'Mendoza' => ['encounters', 'encounters', 'encounters', 'encounters', 'encounters', 'encounters', 'encounters', 'virtual', 'virtual', 'virtual'],
            'San Juan' => ['encounters', 'encounters', 'encounters', 'encounters', 'encounters', 'encounters', 'encounters', 'virtual', 'virtual', 'virtual'],
            'San Luis' => ['encounters', 'encounters', 'encounters', 'encounters', 'encounters', 'encounters', 'virtual', 'virtual', 'virtual', 'virtual'],
        ];

        $plan = [];
        foreach (['Mendoza', 'San Juan', 'San Luis'] as $province) {
            foreach ($typesByProvince[$province] as $i => $type) {
                $spot = $spots[$province][$i % count($spots[$province])];
                $plan[] = [
                    'province' => $province,
                    'locality' => $spot[0],
                    'zone' => $spot[1],
                    'type' => $type,
                ];
            }
        }

        return $plan;
    }

    /** @param array{province:string, locality:string, zone:string, type:string} $item */
    private function seedProfile(array $item, int $number, ModelPhotoImageProcessor $processor, ModelPhotoStorage $storage, mixed $adminId): void
    {
        $stageName = self::FIRST_NAMES[$number - 1].' '.self::LAST_NAMES[$number - 1];

        $user = User::query()->firstOrCreate(
            ['email' => sprintf('modelo-test-%02d@divascuyo.test', $number)],
            [
                'name' => sprintf('Cuenta prueba %02d', $number),
                'password' => Hash::make('password'),
                'whatsapp' => sprintf('+54 9 260 111 11%02d', $number),
                'location' => $item['locality'],
                'is_published' => true,
            ],
        );
        $user->forceFill([
            'email_verified_at' => $user->email_verified_at ?? now(),
            'is_published' => true,
            'is_admin' => false,
        ])->save();

        $province = Province::query()->firstOrCreate(
            ['slug' => Str::slug($item['province'])],
            ['name' => $item['province'], 'is_active' => true],
        );
        $locality = Locality::query()->firstOrCreate(
            ['province_id' => $province->id, 'slug' => Str::slug($item['locality'])],
            ['name' => $item['locality'], 'is_active' => true],
        );
        $publicationType = PublicationType::query()->where('slug', $item['type'])->firstOrFail();

        $profile = $user->modelProfile()->firstOrNew();
        $profile->forceFill([
            'name' => sprintf('Cuenta prueba %02d', $number),
            'whatsapp' => $user->whatsapp,
            'location' => $item['locality'],
            'public_age' => 22 + ($number % 14),
            'show_age' => true,
            'height_cm' => 160 + ($number % 18),
            'weight_kg' => 52 + ($number % 14),
            'measurements' => '90-62-92',
            'eye_color' => 'Marrones',
            'hair_color' => 'Castaño',
            'skin_color' => 'Oliva',
            'body_type' => 'Media',
            'nationality' => 'Argentina',
            'availability_status' => in_array($number, [7, 17, 27], true) ? 'unavailable' : 'available',
            'identity_status' => 'approved',
            'review_status' => 'approved',
            'is_published' => true,
            'publication_type_id' => $publicationType->id,
            'province_id' => $province->id,
            'locality_id' => $locality->id,
            'approximate_location_text' => $item['zone'],
        ])->save();

        $profile = app(ModelProfileSlugService::class)->rename($profile, $stageName);

        $bio = $profile->bios()->firstOrCreate(
            ['content' => self::BIOS[($number - 1) % count(self::BIOS)]],
            ['status' => 'approved'],
        );
        $bio->forceFill([
            'status' => 'approved',
            'reviewed_at' => $bio->reviewed_at ?? now(),
            'reviewed_by' => $bio->reviewed_by ?? $adminId,
        ])->save();
        $profile->forceFill(['current_bio_id' => $bio->id])->save();

        $serviceTypes = $item['type'] === 'virtual' ? ['virtual'] : ['virtual', 'in_person'];
        $serviceIds = Service::query()
            ->whereIn('service_type', $serviceTypes)
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(3)
            ->pluck('id')
            ->all();
        $profile->services()->sync($serviceIds);

        if ($profile->photos()->exists()) {
            return;
        }

        try {
            $file = $this->makeImageFile($stageName, $number);
            $processed = $processor->process($file);
            $paths = $storage->storeVariants((int) $profile->getKey(), $processed['contents'], $processed['extension']);

            $photo = $profile->photos()->create([
                'position' => 1,
                'is_primary' => true,
            ]);
            $version = $photo->versions()->create([
                ...$paths,
                'version' => 1,
                'original_name' => $processed['original_name'],
                'mime_type' => $processed['mime_type'],
                'file_size' => $processed['file_size'],
                'width' => $processed['width'],
                'height' => $processed['height'],
                'processed_width' => $processed['processed_width'],
                'processed_height' => $processed['processed_height'],
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => $adminId,
            ]);
            $version->forceFill(['public_watermarked_at' => now()])->save();
            $photo->forceFill(['current_version_id' => $version->getKey()])->save();
        } catch (\Throwable $exception) {
            $this->command->warn("Perfil {$number} ({$stageName}): no se pudo generar la foto ({$exception->getMessage()}).");
        }
    }

    private function makeImageFile(string $stageName, int $number): UploadedFile
    {
        [$r, $g, $b] = self::PALETTE[($number - 1) % count(self::PALETTE)];
        $width = 720;
        $height = 960;

        $image = imagecreatetruecolor($width, $height);
        $base = imagecolorallocate($image, $r, $g, $b);
        imagefill($image, 0, 0, $base);

        // Franjas diagonales para variar cada foto.
        $stripe = imagecolorallocate($image, min(255, $r + 40), min(255, $g + 40), min(255, $b + 40));
        for ($i = -$height; $i < $width; $i += 60) {
            imageline($image, $i, 0, $i + $height, $height, $stripe);
        }

        // Iniciales al centro.
        $initials = implode('', array_map(fn (string $part): string => strtoupper(mb_substr($part, 0, 1)), explode(' ', $stageName)));
        $textColor = imagecolorallocate($image, 255, 255, 255);
        imagestring($image, 5, (int) ($width / 2 - 20), (int) ($height / 2 - 10), $initials, $textColor);
        imagestring($image, 3, (int) ($width / 2 - 60), (int) ($height / 2 + 20), 'PERFIL DE PRUEBA', $textColor);

        $path = tempnam(sys_get_temp_dir(), 'seed-photo-').'.jpg';
        imagejpeg($image, $path, 85);
        imagedestroy($image);

        return new UploadedFile($path, 'foto-prueba-'.$number.'.jpg', 'image/jpeg', null, true);
    }
}
