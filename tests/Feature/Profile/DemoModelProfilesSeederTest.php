<?php

namespace Tests\Feature\Profile;

use App\Models\ModelProfile;
use App\Models\ModelProfileSlug;
use App\Services\PublicModelHomepageProfiles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoModelProfilesSeederTest extends TestCase
{
    public function test_local_database_seeder_keeps_five_complete_profiles_without_photos_idempotently(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'local');

        $this->seed(DatabaseSeeder::class);
        $profiles = $this->demoProfiles();

        $this->assertCount(5, $profiles);
        $this->assertSame(5, ModelProfileSlug::query()->whereNotNull('model_profile_id')->count());
        foreach ($profiles as $profile) {
            $this->assertNotEmpty($profile->slug);
            $this->assertSame('approved', $profile->identity_status);
            $this->assertSame('approved', $profile->review_status);
            $this->assertTrue($profile->is_published);
            $this->assertNotNull($profile->user->email_verified_at);
            $this->assertTrue(Hash::check('password', $profile->user->password));
            $this->assertNotNull($profile->province);
            $this->assertNotNull($profile->locality);
            $this->assertNotNull($profile->publicationType);
            $this->assertNotEmpty($profile->services);
            $this->assertNotNull($profile->currentBio);
            $this->assertSame('approved', $profile->currentBio->status);
            $this->assertNotEmpty($profile->nationality);
            $this->assertNotEmpty($profile->height_cm);
            $this->assertNotEmpty($profile->weight_kg);
            $this->assertCount(0, $profile->photos);
        }

        $this->assertSame([], app(PublicModelHomepageProfiles::class)->cards(10));

        $this->seed(DatabaseSeeder::class);

        $profilesAfterReseed = $this->demoProfiles();
        $this->assertCount(5, $profilesAfterReseed);
        $this->assertSame(5, ModelProfileSlug::query()->whereNotNull('model_profile_id')->count());
        $this->assertSame([], app(PublicModelHomepageProfiles::class)->cards(10));
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
            ->with(['user', 'province', 'locality', 'publicationType', 'services', 'currentBio', 'photos.currentVersion', 'photos.versions'])
            ->orderBy('slug')
            ->get();
    }
}
