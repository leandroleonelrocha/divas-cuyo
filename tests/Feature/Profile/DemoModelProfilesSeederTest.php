<?php

namespace Tests\Feature\Profile;

use App\Models\ModelProfile;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class DemoModelProfilesSeederTest extends TestCase
{
    public function test_database_seeder_does_not_create_demo_profiles_in_local(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'local');

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, $this->demoProfiles()->count());
        $this->assertDatabaseMissing('users', ['email' => 'modelo-demo-01@divascuyo.test']);
    }

    public function test_database_seeder_does_not_create_demo_profiles_outside_local(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'testing');

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseMissing('users', ['email' => 'modelo-demo-01@divascuyo.test']);
    }

    private function demoProfiles()
    {
        return ModelProfile::query()
            ->whereHas('user', fn ($query) => $query->where('email', 'like', 'modelo-demo-%@divascuyo.test'))
            ->orderBy('slug')
            ->get();
    }
}
