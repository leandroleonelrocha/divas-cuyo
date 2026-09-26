<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_age_is_calculated_dynamically_from_birth_date(): void
    {
        $user = User::factory()->create();
        $details = $user->modelProfile->privateDetails()->create([
            'real_first_name' => 'Mía',
            'real_last_name' => 'Privada',
            'birth_date' => '1990-05-20',
        ]);

        $this->assertSame(Carbon::parse('1990-05-20')->age, $details->realAge());
        $this->assertArrayNotHasKey('real_age', $details->getAttributes());
    }

    public function test_public_age_can_be_up_to_five_years_younger_but_not_older(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        $realAge = Carbon::parse($payload['birth_date'])->age;

        $this->actingAs($user)->patch(route('account.profile.update'), [
            ...$payload,
            'public_age' => $realAge - 5,
        ])->assertRedirect(route('account.profile.edit'));

        $this->assertDatabaseHas('model_profiles', [
            'id' => $user->modelProfile->id,
            'public_age' => $realAge - 5,
        ]);

        $this->actingAs($user)->patch(route('account.profile.update'), [
            ...$payload,
            'public_age' => $realAge + 1,
        ])->assertSessionHasErrors('public_age');

        $this->assertSame($realAge - 5, $user->modelProfile->fresh()->public_age);
    }

    public function test_future_birth_date_is_rejected_and_public_age_does_not_change_birth_date(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();

        $this->actingAs($user)->patch(route('account.profile.update'), [
            ...$payload,
            'birth_date' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors('birth_date');

        $this->actingAs($user)->patch(route('account.profile.update'), [
            ...$payload,
            'public_age' => 31,
        ])->assertRedirect(route('account.profile.edit'));

        $this->assertSame($payload['birth_date'], $user->modelProfile->privateDetails->fresh()->birth_date->format('Y-m-d'));
    }

    public function test_hiding_public_age_does_not_delete_the_configured_age(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();

        $this->actingAs($user)->patch(route('account.profile.update'), [
            ...$payload,
            'public_age' => 31,
            'show_age' => false,
        ])->assertRedirect(route('account.profile.edit'));

        $profile = $user->modelProfile->fresh();
        $this->assertFalse($profile->show_age);
        $this->assertSame(31, $profile->public_age);
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'real_first_name' => 'Mía',
            'real_last_name' => 'Privada',
            'birth_date' => '1990-05-20',
            'stage_name' => 'Mia',
            'show_age' => true,
            'nationality' => 'Argentina',
        ];
    }
}
