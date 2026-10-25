<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->where('email', 'admin@divascuyo.com')->exists()) {
            return;
        }

        (new User)->forceFill([
            'name' => 'admin',
            'email' => 'admin@divascuyo.com',
            'password' => 'admin2026*',
            // La cuenta administrativa no tiene datos de contacto de modelo.
            'whatsapp' => '',
            'location' => '',
            'is_admin' => true,
            'email_verified_at' => now(),
        ])->save();
    }
}
