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
            ['name' => 'Garganta profunda', 'slug' => 'garganta-profunda', 'service_type' => 'in_person'],
            ['name' => 'Domicilios', 'slug' => 'domicilios', 'service_type' => 'in_person'],
            ['name' => 'Onda novia', 'slug' => 'onda-novia', 'service_type' => 'in_person'],
            ['name' => 'Fetichismo', 'slug' => 'fetichismo', 'service_type' => 'in_person'],
            ['name' => 'Lésbico', 'slug' => 'lesbico', 'service_type' => 'in_person'],
            ['name' => 'Striptease', 'slug' => 'striptease', 'service_type' => 'in_person'],
            ['name' => 'Cambio de roles', 'slug' => 'cambio-de-roles', 'service_type' => 'in_person'],
            ['name' => 'Atención a parejas', 'slug' => 'atencion-a-parejas', 'service_type' => 'in_person'],
            ['name' => 'Dominación', 'slug' => 'dominacion', 'service_type' => 'in_person'],
            ['name' => 'Juguetes', 'slug' => 'juguetes', 'service_type' => 'in_person'],
            ['name' => 'Viajes a convenir', 'slug' => 'viajes-a-convenir', 'service_type' => 'in_person'],
            ['name' => 'Despedidas', 'slug' => 'despedidas', 'service_type' => 'in_person'],
            ['name' => 'Oral con preservativo', 'slug' => 'oral-con-preservativo', 'service_type' => 'in_person'],
            ['name' => 'Masajes con final feliz', 'slug' => 'masajes-con-final-feliz', 'service_type' => 'in_person'],
            ['name' => 'Besos negros', 'slug' => 'besos-negros', 'service_type' => 'in_person'],
            ['name' => 'Lluvia dorada', 'slug' => 'lluvia-dorada', 'service_type' => 'in_person'],
            ['name' => 'Sexo anal', 'slug' => 'sexo-anal', 'service_type' => 'in_person'],
            ['name' => 'Eyaculación corporal', 'slug' => 'eyaculacion-corporal', 'service_type' => 'in_person'],
            ['name' => 'Besos', 'slug' => 'besos', 'service_type' => 'in_person'],
            ['name' => 'Masaje relajante', 'slug' => 'masaje-relajante', 'service_type' => 'in_person'],
            ['name' => 'Chat erótico', 'slug' => 'chat-erotico', 'service_type' => 'virtual'],
            ['name' => 'Venta de ropa interior', 'slug' => 'venta-de-ropa-interior', 'service_type' => 'virtual'],
            ['name' => 'Salidas a hoteles', 'slug' => 'salidas-a-hoteles', 'service_type' => 'in_person'],
            ['name' => 'Oral sin preservativo', 'slug' => 'oral-sin-preservativo', 'service_type' => 'in_person'],
            ['name' => 'Fantasías', 'slug' => 'fantasias', 'service_type' => 'in_person'],
            ['name' => 'Squirt', 'slug' => 'squirt', 'service_type' => 'in_person'],
        ];

        foreach ($services as $sortOrder => $service) {
            Service::query()->updateOrCreate(
                ['slug' => $service['slug']],
                $service + ['is_active' => true, 'sort_order' => $sortOrder],
            );
        }
    }
}
