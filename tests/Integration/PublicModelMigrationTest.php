<?php

namespace Tests\Integration;

use App\Models\ModelProfileSlug;
use App\Models\User;
use App\Services\ModelPhotoImageProcessor;
use App\Services\ModelProfileSlugService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Support\PublicModelProfileFixtures;

class PublicModelMigrationTest extends OwnedMySqlSchema
{
    use PublicModelProfileFixtures;

    public function test_clean_schema_forward_and_rollback(): void
    {
        $this->migrateAll();
        $this->assertTrue(Schema::hasColumn('model_profiles', 'slug'));
        $this->assertTrue(Schema::hasColumn('model_photo_versions', 'public_token'));
        $this->assertSame(0, DB::table('model_profiles')->count());
        fwrite(STDOUT, "\nT056 clean: ".DB::table('migrations')->count()." migrations; 0 profiles\n");
    }

    public function test_upgrade_006_backfill_repeat_photo_preparation_and_schema_rollback(): void
    {
        $legacy = array_filter(glob(database_path('migrations/*.php')), fn ($path) => basename($path) < '2026_09_29');
        $this->artisan('migrate', ['--path' => array_values($legacy), '--realpath' => true, '--force' => true])->assertExitCode(0);
        $this->assertFalse(Schema::hasColumn('model_profiles', 'slug'));
        $users = User::factory()->count(101)->create();
        DB::table('model_profiles')->update(['stage_name' => 'Mia']);
        $private = $users->first()->modelProfile->privateDetails()->create(['real_first_name' => 'SYNTHETIC', 'real_last_name' => 'TEST', 'birth_date' => '1990-05-20']);
        $before = $private->fresh()->getAttributes();
        $photoId = DB::table('model_photos')->insertGetId(['model_profile_id' => $users->first()->modelProfile->id]);
        $legacyVersionId = DB::table('model_photo_versions')->insertGetId([
            'model_photo_id' => $photoId, 'version' => 1, 'original_path' => 'legacy/original.jpg',
            'processed_path' => 'legacy/processed.webp', 'public_path' => 'legacy/public.webp',
            'thumbnail_path' => 'legacy/thumbnail.webp', 'mime_type' => 'image/webp',
            'file_size' => 100, 'width' => 80, 'height' => 80, 'processed_width' => 80, 'processed_height' => 80,
        ]);
        $legacyBefore = (array) DB::table('model_photo_versions')->find($legacyVersionId);
        $this->migrateAll();
        $this->assertSame(101, ModelProfileSlug::count());
        $legacyAfter = (array) DB::table('model_photo_versions')->find($legacyVersionId);
        $this->assertNotNull($legacyAfter['public_token']);
        $this->assertNull($legacyAfter['public_watermarked_at']);
        $this->assertSame($legacyBefore, array_diff_key($legacyAfter, array_flip(['public_token', 'public_watermarked_at'])));
        $this->assertSame('mia-101', $users->last()->modelProfile->fresh()->slug);
        $this->assertSame($before, $private->fresh()->getAttributes());
        $migration = require database_path('migrations/2026_09_29_000004_backfill_public_profile_slugs.php');
        $reservations = DB::table('model_profile_slugs')->orderBy('id')->get()->toJson();
        $migration->up();
        $migration->down();
        $this->assertSame($reservations, DB::table('model_profile_slugs')->orderBy('id')->get()->toJson());
        $this->migrateAll();

        $profile = $this->publicProfile('photo-test');
        $version = $profile->photos()->first()->currentVersion;
        $disk = Storage::disk('model_photos');
        $original = 'synthetic original bytes';
        $disk->put($version->original_path, $original);
        $processed = app(ModelPhotoImageProcessor::class)->process(UploadedFile::fake()->image('source.jpg', 800, 800));
        $disk->put($version->processed_path, $processed['contents']['processed']);
        $version->forceFill(['public_watermarked_at' => null, 'public_token' => null])->save();
        $tokenMigration = require database_path('migrations/2026_09_29_000006_backfill_public_photo_tokens.php');
        $tokenMigration->up();
        $version->refresh();
        $this->assertNotNull($version->public_token);
        $tokenMigration->up();
        $this->assertSame($version->public_token, $version->fresh()->public_token);
        $this->get('/modelos/photo-test')->assertNotFound();
        $state = $version->getAttributes();
        $files = $disk->allFiles();
        $this->artisan('model-photos:prepare-public', ['--dry-run' => true])->expectsOutput('Versiones por preparar: 1')->assertExitCode(0);
        $this->assertSame($state, $version->fresh()->getAttributes());
        $this->assertSame($files, $disk->allFiles());
        $this->artisan('model-photos:prepare-public')->expectsOutput('Preparadas: 1. Fallidas: 0.')->assertExitCode(0);
        $ready = $version->fresh()->getAttributes();
        $this->get('/modelos/photo-test')->assertOk();
        $this->artisan('model-photos:prepare-public')->expectsOutput('Preparadas: 0. Fallidas: 0.')->assertExitCode(0);
        $this->assertSame($ready, $version->fresh()->getAttributes());
        $this->assertSame($original, $disk->get($version->original_path));
        $this->assertSame($processed['contents']['processed'], $disk->get($version->processed_path));

        app(ModelProfileSlugService::class)->rename($profile, 'Renamed photo');
        DB::table('model_profile_slugs')->insert(['slug' => 'deleted-reservation', 'model_profile_id' => null]);
        // Backup includes aliases/tombstones that cannot be rebuilt from current slugs.
        $backup = ['profiles' => DB::table('model_profiles')->pluck('slug', 'id')->all(), 'reservations' => DB::table('model_profile_slugs')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all()];
        Storage::disk('model_photos')->put('rollback-reservations.json', json_encode($backup, JSON_THROW_ON_ERROR));
        $this->artisan('migrate:rollback', ['--step' => 6, '--force' => true])->assertExitCode(0);
        $this->assertFalse(Schema::hasColumn('model_profiles', 'slug'));
        $this->assertFalse(Schema::hasTable('model_profile_slugs'));
        $this->assertSame(102, DB::table('model_profiles')->count());
        $this->assertSame($before, $private->fresh()->getAttributes());
        $this->assertSame($original, $disk->get($version->original_path));
        $this->migrateAll();
        $this->assertFalse(DB::table('model_profile_slugs')->where('slug', 'deleted-reservation')->exists());
        DB::table('model_profile_slugs')->delete();
        DB::table('model_profile_slugs')->insert($backup['reservations']);
        $this->assertSame($backup['profiles'], DB::table('model_profiles')->pluck('slug', 'id')->all());
        $this->assertSame($backup['reservations'], DB::table('model_profile_slugs')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all());
        fwrite(STDOUT, "\nT056 upgrade: 101 backfilled + 1 photo profile; dry-run 1, real 1, repeat 0; original/processed preserved; 104 reservations backed up/restored; rollback to 006 PASS\n");
    }
}
