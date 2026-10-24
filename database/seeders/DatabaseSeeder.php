<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ProvinceSeeder::class);
        $this->call(LocalitySeeder::class);
        $this->call(PublicationTypeSeeder::class);
        $this->call(ServiceSeeder::class);
        $this->call(AdminUserSeeder::class);

        if (config('divas.seed_real_profiles')) {
            $this->call(PerfilesDivasCuyoSeeder::class);
        }

    }
}
