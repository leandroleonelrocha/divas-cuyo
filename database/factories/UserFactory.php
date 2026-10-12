<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->modelProfile()->create([
                'name' => $user->name,
                'whatsapp' => $user->whatsapp,
                'location' => $user->location,
                'is_published' => $user->is_published,
                'review_status' => $user->is_published ? 'approved' : 'pending',
            ]);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Model '.Str::upper(Str::random(8)),
            'email' => Str::lower(Str::random(12)).'@example.test',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'whatsapp' => '+54 9 261 '.random_int(100, 999).' '.random_int(1000, 9999),
            'location' => 'Mendoza',
            'is_published' => false,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
