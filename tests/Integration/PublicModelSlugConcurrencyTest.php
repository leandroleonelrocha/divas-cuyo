<?php

namespace Tests\Integration;

use App\Models\ModelProfile;
use App\Models\ModelProfileSlug;
use App\Models\User;
use App\Services\ModelProfileSlugService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class PublicModelSlugConcurrencyTest extends OwnedMySqlSchema
{
    public function test_real_commits_collisions_serialized_renames_rollback_and_bounded_retries(): void
    {
        $this->migrateAll();
        $a = User::factory()->create()->modelProfile;
        $b = User::factory()->create()->modelProfile;
        $results = $this->race([[$a->id, 'Mia'], [$b->id, 'Mia']]);
        $this->assertCount(2, array_unique(array_column($results, 'connection_id')));
        $slugs = array_column($results, 'slug');
        sort($slugs);
        $this->assertSame(['mia', 'mia-2'], $slugs);
        $this->assertSame(2, ModelProfileSlug::count());
        $service = app(ModelProfileSlugService::class);
        $owner = ModelProfile::where('slug', 'mia')->firstOrFail();
        $service->rename($owner, 'Luna');
        $c = User::factory()->create()->modelProfile;
        $d = User::factory()->create()->modelProfile;
        $results = $this->race([[$c->id, 'Mia'], [$d->id, 'Mia']]);
        $slugs = array_column($results, 'slug');
        sort($slugs);
        $this->assertSame(['mia-3', 'mia-4'], $slugs);
        $this->assertSame($owner->id, ModelProfileSlug::where('slug', 'mia')->first()->model_profile_id);

        // Both workers start with the same stale object while the parent holds its row lock.
        DB::beginTransaction();
        ModelProfile::whereKey($owner->id)->lockForUpdate()->first();
        $results = $this->race([[$owner->id, 'Sol'], [$owner->id, 'Estrella']], true);
        $this->assertEqualsCanonicalizing(['sol', 'estrella'], array_column($results, 'slug'));
        $owner->refresh();
        $this->assertSame(strtolower($owner->stage_name), $owner->slug);
        foreach (['mia', 'luna', 'sol', 'estrella'] as $slug) {
            $this->assertSame($owner->id, ModelProfileSlug::where('slug', $slug)->first()->model_profile_id);
        }
        $before = $owner->getAttributes();
        DB::beginTransaction();
        $service->rename($owner, 'Rollback');
        DB::rollBack();
        $this->assertSame($before, $owner->fresh()->getAttributes());
        $this->assertFalse(ModelProfileSlug::where('slug', 'rollback')->exists());
        ModelProfile::updating(function (ModelProfile $profile): void {
            if ($profile->stage_name === 'Failure') {
                throw new \RuntimeException('Synthetic failure after reservation');
            }
        });
        try {
            $service->rename($owner, 'Failure');
            $this->fail('Expected a failure before saving the profile.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Synthetic failure after reservation', $exception->getMessage());
        }
        $this->assertSame($before, $owner->fresh()->getAttributes());
        $this->assertFalse(ModelProfileSlug::where('slug', 'failure')->exists());

        DB::beginTransaction();
        ModelProfile::whereKey($owner->id)->lockForUpdate()->first();
        try {
            $result = $this->race([[$owner->id, 'Timeout']], false, true)[0];
            $this->assertSame(3, $result['attempts']);
            $this->assertArrayHasKey('error', $result);
        } finally {
            DB::rollBack();
        }
        $this->assertSame($before, $owner->fresh()->getAttributes());
        $this->assertFalse(ModelProfileSlug::where('slug', 'timeout')->exists());
        $owner->delete();
        $this->assertNull(ModelProfileSlug::where('slug', 'mia')->first()->model_profile_id);
        $this->assertSame('mia-5', $service->rename(User::factory()->create()->modelProfile, 'Mia')->slug);
        fwrite(STDOUT, "\nT053: concurrent commits, reserved alias, serialized renames, rollback, 3 lock retries, tombstone PASS\n");
    }

    private function race(array $jobs, bool $releaseLock = false, bool $expectFailure = false): array
    {
        $workers = [];
        $gate = sys_get_temp_dir().'/mysql-slug-'.bin2hex(random_bytes(12));
        try {
            foreach ($jobs as [$id, $name]) {
                $process = new Process([PHP_BINARY, base_path('tests/Support/mysql-slug-worker.php'), (string) $id, $name, $gate], base_path(), null, null, 20);
                $process->start();
                $workers[] = [$process];
            }
            foreach ($workers as [$process]) {
                $deadline = microtime(true) + 10;
                while (! str_contains($process->getOutput(), 'READY') && $process->isRunning() && microtime(true) < $deadline) {
                    usleep(10000);
                }
                $this->assertStringContainsString('READY', $process->getOutput(), $process->getErrorOutput());
            }
            touch($gate);
            if ($releaseLock) {
                usleep(250000);
                foreach ($workers as [$process]) {
                    $this->assertTrue($process->isRunning(), 'Worker must wait for held row lock.');
                }
                DB::commit();
            }
            $results = [];
            foreach ($workers as [$process]) {
                $this->assertSame($expectFailure ? 2 : 0, $process->wait(), $process->getErrorOutput().$process->getOutput());
                $lines = explode("\n", trim($process->getOutput()));
                $result = json_decode(end($lines), true, flags: JSON_THROW_ON_ERROR);
                $this->assertLessThanOrEqual(3, $result['attempts']);
                $results[] = $result;
            }

            return $results;
        } finally {
            if (file_exists($gate)) {
                unlink($gate);
            }
            foreach ($workers as [$process]) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
        }
    }
}
