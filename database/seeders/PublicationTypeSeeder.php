<?php

namespace Database\Seeders;

use App\Models\PublicationType;
use Illuminate\Database\Seeder;

class PublicationTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [
                'name' => 'Solo Virtual',
                'slug' => 'virtual',
                'allows_in_person_services' => false,
            ],
            [
                'name' => 'Encuentros',
                'slug' => 'encounters',
                'allows_in_person_services' => true,
            ],
        ] as $type) {
            PublicationType::query()->updateOrCreate(['slug' => $type['slug']], $type + ['is_active' => true]);
        }
    }
}
