<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;

class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Mendoza', 'slug' => 'mendoza'],
            ['name' => 'San Juan', 'slug' => 'san-juan'],
            ['name' => 'San Luis', 'slug' => 'san-luis'],
        ] as $province) {
            Province::query()->updateOrCreate(['slug' => $province['slug']], $province);
        }
    }
}
