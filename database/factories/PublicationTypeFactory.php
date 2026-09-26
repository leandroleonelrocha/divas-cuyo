<?php

namespace Database\Factories;

use App\Models\PublicationType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PublicationType> */
class PublicationTypeFactory extends Factory
{
    protected $model = PublicationType::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'allows_in_person_services' => false,
            'is_active' => true,
        ];
    }
}
