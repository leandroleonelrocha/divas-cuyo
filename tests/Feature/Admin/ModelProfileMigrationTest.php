<?php

namespace Tests\Feature\Admin;

use App\Models\ModelProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ModelProfileMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_one_model_profile_with_the_expected_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Modelo Mendoza',
            'whatsapp' => '+54 9 261 555 1234',
            'location' => 'Mendoza',
            'is_published' => false,
        ]);

        $profile = ModelProfile::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertTrue($user->modelProfile->is($profile));
        $this->assertTrue($profile->user->is($user));
        $this->assertSame('Modelo Mendoza', $profile->name);
        $this->assertSame('pending', $profile->review_status);
        $this->assertFalse($profile->is_published);
    }

    public function test_registration_defers_profile_creation_until_after_verification(): void
    {
        Notification::fake();
        $this->post(route('register.store'), [
            'email' => 'modelo@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('registration.pending'));

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('model_profiles', 0);
    }
}
