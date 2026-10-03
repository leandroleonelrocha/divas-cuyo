<?php

namespace Tests\Feature\PublicModels;

use App\Models\ModelProfileSlug;
use App\Models\User;
use App\Services\ModelProfileDetailsService;
use App\Services\ModelProfileSlugService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicModelSlugPersistenceTest extends TestCase
{
    public function test_initial_generation_collisions_and_historical_reservations(): void
    {
        $service = app(ModelProfileSlugService::class);
        $first = $service->rename(User::factory()->create()->modelProfile, 'Mia');
        $second = $service->rename(User::factory()->create()->modelProfile, 'Mia');
        $this->assertSame('mia', $first->slug);
        $this->assertSame('mia-2', $second->slug);
        $service->rename($first, 'Luna');
        $third = $service->rename(User::factory()->create()->modelProfile, 'Mia');
        $this->assertSame('mia-3', $third->slug);
        $this->assertSame('mia', $service->rename($first, 'Mia')->slug);
        $this->assertSame(2, $first->slugs()->count());
        $this->assertSame('mia-2', $service->rename($second, 'Mía!')->slug);
    }

    public function test_deleted_profile_and_explicit_reservations_are_not_reassigned(): void
    {
        $service = app(ModelProfileSlugService::class);
        $profile = $service->rename(User::factory()->create()->modelProfile, 'Mia');
        $profile->delete();
        $this->assertDatabaseHas('model_profile_slugs', ['slug' => 'mia', 'model_profile_id' => null]);
        $this->assertSame('mia-2', $service->rename(User::factory()->create()->modelProfile, 'Mia')->slug);
    }

    public function test_suffix_fits_length_and_number_never_becomes_numeric_slug(): void
    {
        $service = app(ModelProfileSlugService::class);
        $service->rename(User::factory()->create()->modelProfile, str_repeat('a', 200));
        $other = $service->rename(User::factory()->create()->modelProfile, str_repeat('a', 200));
        $this->assertSame(str_repeat('a', 158).'-2', $other->slug);
        $this->assertSame('modelo-123', $service->rename($other, '123')->slug);
    }

    public function test_outer_rollback_restores_name_slug_and_reservations(): void
    {
        $service = app(ModelProfileSlugService::class);
        $profile = $service->rename(User::factory()->create()->modelProfile, 'Mia');
        try {
            DB::transaction(function () use ($service, $profile) {
                $service->rename($profile, 'Luna');
                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException) {
        }
        $this->assertSame('mia', $profile->fresh()->slug);
        $this->assertSame('Mia', $profile->fresh()->stage_name);
        $this->assertDatabaseMissing('model_profile_slugs', ['slug' => 'luna']);
    }

    public function test_private_profile_update_generates_slug_and_rejects_manual_assignment(): void
    {
        $user = User::factory()->create();
        $data = ['stage_name' => 'Mia', 'real_first_name' => 'PRIVATE_NAME', 'real_last_name' => 'PRIVATE_LAST', 'birth_date' => '1990-05-20', 'show_age' => false, 'nationality' => 'Argentina'];
        $this->actingAs($user)->patch(route('account.profile.update'), $data + ['slug' => '123'])
            ->assertSessionHasErrors('slug');
        $this->assertNull($user->modelProfile->fresh()->slug);
        $this->actingAs($user)->patch(route('account.profile.update'), $data)->assertSessionHasNoErrors();
        $this->assertSame('mia', $user->modelProfile->fresh()->slug);
        $this->assertDatabaseHas('model_profile_slugs', ['slug' => 'mia', 'model_profile_id' => $user->modelProfile->id]);
        $this->actingAs($user)->patch(route('account.profile.update'), array_replace($data, ['stage_name' => 'Luna']))->assertSessionHasNoErrors();
        $this->assertSame('luna', $user->modelProfile->fresh()->slug);
        $this->assertSame('PRIVATE_NAME', $user->modelProfile->privateDetails->real_first_name);
    }

    public function test_details_transaction_rolls_back_private_changes_when_slug_assignment_fails(): void
    {
        $user = User::factory()->create();
        $this->mock(ModelProfileSlugService::class, function ($mock) {
            $mock->shouldReceive('rename')->andThrow(new \RuntimeException('simulated failure'));
        });
        try {
            app(ModelProfileDetailsService::class)->update($user->modelProfile, $user, ['stage_name' => 'Mia', 'real_first_name' => 'PRIVATE_NAME', 'real_last_name' => 'PRIVATE_LAST', 'birth_date' => '1990-05-20', 'show_age' => false, 'nationality' => 'Argentina']);
            $this->fail('Expected failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('simulated failure', $exception->getMessage());
        }
        $this->assertNull($user->modelProfile->fresh()->stage_name);
        $this->assertDatabaseMissing('model_profile_private_details', ['model_profile_id' => $user->modelProfile->id]);
    }

    public function test_reservation_unique_constraint_cannot_be_bypassed(): void
    {
        $profile = app(ModelProfileSlugService::class)->rename(User::factory()->create()->modelProfile, 'Mia');
        $this->expectException(UniqueConstraintViolationException::class);
        (new ModelProfileSlug)->forceFill(['slug' => 'mia', 'model_profile_id' => $profile->id])->save();
    }
}
