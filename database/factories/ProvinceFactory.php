<?php

namespace Database\Factories;

use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Province> */
class ProvinceFactory extends Factory
{
    protected $model = Province::class;

    public function definition(): array
    {
        $name = fake()->unique()->state();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => true,
        ];
    }
}
