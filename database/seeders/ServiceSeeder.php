<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'Videollamada', 'slug' => 'videollamada', 'service_type' => 'virtual'],
            ['name' => 'Sexting', 'slug' => 'sexting', 'service_type' => 'virtual'],
            ['name' => 'Novia virtual', 'slug' => 'novia-virtual', 'service_type' => 'virtual'],
            ['name' => 'Packs de fotos', 'slug' => 'packs-de-fotos', 'service_type' => 'virtual'],
            ['name' => 'Packs de videos', 'slug' => 'packs-de-videos', 'service_type' => 'virtual'],
            ['name' => 'Videos personalizados', 'slug' => 'videos-personalizados', 'service_type' => 'virtual'],
            ['name' => 'Chat virtual', 'slug' => 'chat-virtual', 'service_type' => 'virtual'],
            ['name' => 'Encuentros', 'slug' => 'encuentros', 'service_type' => 'in_person'],
            ['name' => 'Departamento propio', 'slug' => 'departamento-propio', 'service_type' => 'in_person'],
            ['name' => 'Salidas', 'slug' => 'salidas', 'service_type' => 'in_person'],
            ['name' => 'Hoteles', 'slug' => 'hoteles', 'service_type' => 'in_person'],
            ['name' => 'Viajes', 'slug' => 'viajes', 'service_type' => 'in_person'],
        ];

        foreach ($services as $sortOrder => $service) {
            Service::query()->updateOrCreate(
                ['slug' => $service['slug']],
                $service + ['is_active' => true, 'sort_order' => $sortOrder],
            );
        }
    }
}
