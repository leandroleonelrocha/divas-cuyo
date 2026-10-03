<?php

namespace Tests\Feature\PublicModels;

use App\Models\ModelProfileSlug;
use App\Models\User;
use App\Services\ModelProfileSlugService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicModelSlugBackfillTest extends TestCase
{
    public function test_backfill_reserves_existing_before_assigning_missing_and_is_idempotent(): void
    {
        $missing = User::factory()->create()->modelProfile;
        $missing->update(['stage_name' => 'Mia']);
        $existing = User::factory()->create()->modelProfile;
        $existing->forceFill(['stage_name' => 'Different name', 'slug' => 'mia'])->save();
        $legacy = User::factory()->create()->modelProfile;
        $blank = User::factory()->create()->modelProfile;
        $blank->update(['stage_name' => '   ']);
        $private = $missing->privateDetails()->create(['real_first_name' => 'SECRET', 'real_last_name' => 'OTHER', 'birth_date' => '1990-05-20']);
        $before = $private->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_09_29_000004_backfill_public_profile_slugs.php');
        $migration->up();
        $this->assertSame('mia-2', $missing->fresh()->slug);
        $this->assertSame('mia', $existing->fresh()->slug);
        $this->assertNull($legacy->fresh()->slug);
        $this->assertNull($blank->fresh()->slug);
        $this->assertSame($before, $private->fresh()->getAttributes());
        $reservations = ModelProfileSlug::orderBy('id')->get()->toArray();
        $migration->up();
        $this->assertSame($reservations, ModelProfileSlug::orderBy('id')->get()->toArray());
        $migration->down();
        $this->assertSame('mia-2', $missing->fresh()->slug);
    }

    public function test_backfill_respects_tombstones_and_preserves_existing_assignment_on_rerun(): void
    {
        (new ModelProfileSlug)->forceFill(['slug' => 'mia', 'model_profile_id' => null])->save();
        $profile = User::factory()->create()->modelProfile;
        $profile->update(['stage_name' => 'Mia']);
        $migration = require database_path('migrations/2026_09_29_000004_backfill_public_profile_slugs.php');
        $migration->up();
        $this->assertSame('mia-2', $profile->fresh()->slug);
        $profile->forceFill(['slug' => 'already-assigned'])->save();
        $migration->up();
        $this->assertSame('already-assigned', $profile->fresh()->slug);
        $this->assertDatabaseHas('model_profile_slugs', ['slug' => 'already-assigned', 'model_profile_id' => $profile->id]);
    }

    public function test_backfill_rechecks_assignment_after_selecting_a_batch(): void
    {
        $profile = User::factory()->create()->modelProfile;
        $profile->update(['stage_name' => 'Mia']);
        $assigned = false;
        DB::listen(function (QueryExecuted $query) use ($profile, &$assigned): void {
            if (! $assigned && str_contains($query->sql, '"slug" is null') && str_contains($query->sql, '"model_profiles"')) {
                $assigned = true;
                app(ModelProfileSlugService::class)->rename($profile, 'Luna');
            }
        });
        $migration = require database_path('migrations/2026_09_29_000004_backfill_public_profile_slugs.php');
        $migration->up();
        $this->assertTrue($assigned);
        $this->assertSame('luna', $profile->fresh()->slug);
        $this->assertDatabaseMissing('model_profile_slugs', ['slug' => 'mia']);
    }

    public function test_backfill_processes_more_than_one_batch_in_id_order(): void
    {
        $users = User::factory()->count(101)->create();
        foreach ($users as $user) {
            $user->modelProfile->update(['stage_name' => 'Mia']);
        }
        $migration = require database_path('migrations/2026_09_29_000004_backfill_public_profile_slugs.php');
        $migration->up();
        $this->assertSame('mia', $users->first()->modelProfile->fresh()->slug);
        $this->assertSame('mia-101', $users->last()->modelProfile->fresh()->slug);
        $this->assertSame(101, ModelProfileSlug::count());
    }
}
