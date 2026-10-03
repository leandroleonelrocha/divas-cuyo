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

class DemoModelProfilesSeeder extends Seeder
{
    private const PROFILES = [
        [
            'stage_name' => 'Alma Ríos',
            'province' => 'Mendoza',
            'locality' => 'Ciudad de Mendoza',
            'zone' => 'Zona centro',
            'type' => 'virtual',
            'services' => ['videollamada', 'sexting', 'packs-de-fotos'],
            'bio' => 'Perfil de demostración. Disfruto conversar, escuchar música y compartir una experiencia virtual cuidada.',
            'public_age' => 28,
            'height_cm' => 168,
            'weight_kg' => 55,
            'measurements' => '90-62-92',
            'eye_color' => 'Marrones',
            'hair_color' => 'Castaño oscuro',
            'skin_color' => 'Oliva',
            'body_type' => 'Delgada',
            'nationality' => 'Argentina',
            'availability_status' => 'available',
        ],
        [
            'stage_name' => 'Bianca Sol',
            'province' => 'San Juan',
            'locality' => 'Rawson',
            'zone' => 'Zona sur',
            'type' => 'encounters',
            'services' => ['videollamada', 'encuentros', 'hoteles'],
            'bio' => 'Perfil de demostración. Me gustan los paseos al aire libre, el cine y las charlas tranquilas.',
            'public_age' => 31,
            'height_cm' => 172,
            'weight_kg' => 61,
            'measurements' => '94-66-96',
            'eye_color' => 'Verdes',
            'hair_color' => 'Rubio oscuro',
            'skin_color' => 'Clara',
            'body_type' => 'Atlética',
            'nationality' => 'Argentina',
            'availability_status' => 'available',
        ],
        [
            'stage_name' => 'Camila Paz',
            'province' => 'San Luis',
            'locality' => 'Ciudad de San Luis',
            'zone' => 'Centro',
            'type' => 'virtual',
            'services' => ['packs-de-videos', 'videos-personalizados', 'chat-virtual'],
            'bio' => 'Perfil de demostración. Soy curiosa, creativa y fanática de los libros y la fotografía.',
            'public_age' => 25,
            'height_cm' => 162,
            'weight_kg' => 52,
            'measurements' => '86-60-90',
            'eye_color' => 'Marrones',
            'hair_color' => 'Negro',
            'skin_color' => 'Morena',
            'body_type' => 'Petite',
            'nationality' => 'Argentina',
            'availability_status' => 'available',
        ],
        [
            'stage_name' => 'Delfina Cruz',
            'province' => 'Mendoza',
            'locality' => 'Ciudad de Mendoza',
            'zone' => 'Quinta sección',
            'type' => 'encounters',
            'services' => ['novia-virtual', 'salidas', 'viajes'],
            'bio' => 'Perfil de demostración. Valoro el buen humor, la música en vivo y los encuentros respetuosos.',
            'public_age' => 34,
            'height_cm' => 175,
            'weight_kg' => 64,
            'measurements' => '96-68-98',
            'eye_color' => 'Miel',
            'hair_color' => 'Cobrizo',
            'skin_color' => 'Clara',
            'body_type' => 'Curvy',
            'nationality' => 'Argentina',
            'availability_status' => 'unavailable',
        ],
        [
            'stage_name' => 'Emilia Luna',
            'province' => 'San Juan',
            'locality' => 'Rawson',
            'zone' => 'Zona oeste',
            'type' => 'virtual',
            'services' => ['videollamada', 'sexting', 'videos-personalizados'],
            'bio' => 'Perfil de demostración. Disfruto el arte, la cocina y conocer nuevas historias.',
            'public_age' => 29,
            'height_cm' => 166,
            'weight_kg' => 57,
            'measurements' => '90-64-94',
            'eye_color' => 'Avellana',
            'hair_color' => 'Castaño',
            'skin_color' => 'Oliva',
            'body_type' => 'Media',
            'nationality' => 'Argentina',
            'availability_status' => 'available',
        ],
    ];

    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        foreach (self::PROFILES as $index => $data) {
            $this->seedProfile($data, $index + 1);
        }
    }

    /** @param array<string, mixed> $data */
    private function seedProfile(array $data, int $number): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => sprintf('modelo-demo-%02d@divascuyo.test', $number)],
            [
                'name' => sprintf('Cuenta demo %02d', $number),
                'password' => Hash::make('password'),
                'whatsapp' => sprintf('+54 9 261 000 00%02d', $number),
                'location' => $data['locality'],
                'is_published' => true,
            ],
        );
        $user->forceFill([
            'email_verified_at' => $user->email_verified_at ?? now(),
            'is_published' => true,
            'is_admin' => false,
        ])->save();

        $province = Province::query()->firstOrCreate(
            ['slug' => Str::slug($data['province'])],
            ['name' => $data['province']],
        );
        $locality = Locality::query()->firstOrCreate(
            ['province_id' => $province->id, 'slug' => Str::slug($data['locality'])],
            ['name' => $data['locality']],
        );
        $publicationType = PublicationType::query()->where('slug', $data['type'])->firstOrFail();
        $profile = $user->modelProfile()->firstOrNew();
        $profile->forceFill([
            'name' => sprintf('Cuenta demo %02d', $number),
            'whatsapp' => $user->whatsapp,
            'location' => $data['locality'],
            'public_age' => $data['public_age'],
            'show_age' => true,
            'height_cm' => $data['height_cm'],
            'weight_kg' => $data['weight_kg'],
            'measurements' => $data['measurements'],
            'eye_color' => $data['eye_color'],
            'hair_color' => $data['hair_color'],
            'skin_color' => $data['skin_color'],
            'body_type' => $data['body_type'],
            'nationality' => $data['nationality'],
            'availability_status' => $data['availability_status'],
            'identity_status' => 'approved',
            'review_status' => 'approved',
            'is_published' => true,
            'publication_type_id' => $publicationType->id,
            'province_id' => $province->id,
            'locality_id' => $locality->id,
            'approximate_location_text' => $data['zone'],
        ])->save();

        $profile = app(ModelProfileSlugService::class)->rename($profile, $data['stage_name']);
        $bio = $profile->bios()->firstOrCreate(
            ['content' => $data['bio']],
            ['status' => 'approved'],
        );
        $bio->forceFill([
            'status' => 'approved',
            'reviewed_at' => $bio->reviewed_at ?? now(),
        ])->save();
        $profile->forceFill(['current_bio_id' => $bio->id])->save();

        $serviceIds = Service::query()
            ->whereIn('slug', $data['services'])
            ->where('is_active', true)
            ->pluck('id')
            ->all();
        $profile->services()->sync($serviceIds);
    }
}
