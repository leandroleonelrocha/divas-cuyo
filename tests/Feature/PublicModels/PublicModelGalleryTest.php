<?php

namespace Tests\Feature\PublicModels;

use App\Models\User;
use App\Services\ModelPhotoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class PublicModelGalleryTest extends TestCase
{
    use PublicModelProfileFixtures;

    public function test_gallery_includes_primary_in_position_and_exposes_only_safe_projection(): void
    {
        $profile = $this->publicProfile();
        $primary = $profile->photos()->first();
        $primary->update(['position' => 3]);
        $second = $this->publicPhoto($profile, 1);
        $missing = $this->publicPhoto($profile, 2);
        Storage::disk('model_photos')->delete($missing->currentVersion->public_path);
        $response = $this->get('/modelos/mia')->assertOk();
        $data = $response->viewData('publicProfile');
        $this->assertSame([1, 3], array_column($data->photos, 'position'));
        $this->assertSame($data->primaryPhoto, $data->photos[1]);
        $this->assertSame(['url', 'alt', 'position', 'width', 'height'], array_keys($data->primaryPhoto));
        $this->assertSame('Foto de Mia', $data->primaryPhoto['alt']);
        $response->assertSee('/modelos/fotos/'.$second->currentVersion->public_token, false);
        foreach (['original_path', 'processed_path', 'thumbnail_path', 'public_path', 'status', 'reviewed_by', 'rejection_reason', 'model_photo_id'] as $key) {
            $this->assertStringNotContainsString($key, json_encode($data));
        }
        $response->assertDontSee($primary->currentVersion->public_path, false);
        $response->assertSee('loading="lazy"', false)->assertSee('loading="eager"', false);
    }

    public function test_pending_and_rejected_replacements_keep_current_until_approval(): void
    {
        $profile = $this->publicProfile();
        $photo = $profile->photos()->first();
        $oldUrl = '/modelos/fotos/'.$photo->currentVersion->public_token;
        $service = app(ModelPhotoService::class);
        $service->replaceForAuthenticatedModel($profile->user, $photo, UploadedFile::fake()->image('replacement.jpg', 800, 800));
        $pending = $photo->fresh()->latestVersion;
        $this->get('/modelos/mia')->assertSee($oldUrl, false)->assertDontSee($pending->public_token, false);
        $this->get('/modelos/fotos/'.$pending->public_token)->assertNotFound();
        $admin = User::factory()->create(['is_admin' => true]);
        $service->moderatePendingVersion($photo, $admin, 'rejected', 'Necesita corrección');
        $this->get('/modelos/mia')->assertSee($oldUrl, false);
        $this->get($oldUrl)->assertOk();
        $service->replaceForAuthenticatedModel($profile->user, $photo, UploadedFile::fake()->image('replacement2.jpg', 800, 800));
        $service->moderatePendingVersion($photo, $admin, 'approved');
        $this->get($oldUrl)->assertNotFound();
        $this->get('/modelos/mia')->assertOk()->assertDontSee($oldUrl, false);
        $this->get('/modelos/fotos/'.$photo->fresh()->currentVersion->public_token)->assertOk();
    }

    public function test_deleting_primary_uses_existing_reassignment_then_denies_when_none_remains(): void
    {
        $profile = $this->publicProfile();
        $primary = $profile->photos()->first();
        $second = $this->publicPhoto($profile, 1);
        $service = app(ModelPhotoService::class);
        $service->deleteForAuthenticatedModel($profile->user, $primary);
        $this->assertTrue($second->fresh()->is_primary);
        $this->get('/modelos/mia')->assertOk();
        $service->deleteForAuthenticatedModel($profile->user, $second);
        $this->get('/modelos/mia')->assertNotFound();
        $this->assertTrue($profile->fresh()->is_published);
    }

    public function test_gallery_uses_central_limit_without_loading_version_history(): void
    {
        $profile = $this->publicProfile();
        for ($position = 1; $position < config('model-photos.max_photos'); $position++) {
            $this->publicPhoto($profile, $position);
        }
        $response = $this->get('/modelos/mia')->assertOk();
        $this->assertCount(config('model-photos.max_photos'), $response->viewData('publicProfile')->photos);
    }

    public function test_gallery_omits_invalid_secondaries_and_approved_history(): void
    {
        $profile = $this->publicProfile();
        $second = $this->publicPhoto($profile, 1);
        $version = $second->currentVersion;
        $publicPath = $version->public_path;
        $history = $version->replicate(['public_token']);
        $history->version = 2;
        $history->save();
        foreach (['pending', 'rejected', 'unmarked', 'private', 'no-current', 'foreign'] as $case) {
            $second->update(['current_version_id' => $version->id]);
            $version->forceFill([
                'status' => 'approved', 'public_watermarked_at' => now(),
                'public_path' => $publicPath,
            ])->save();
            match ($case) {
                'pending', 'rejected' => $version->update(['status' => $case]),
                'unmarked' => $version->forceFill(['public_watermarked_at' => null])->save(),
                'private' => $version->update(['public_path' => $version->thumbnail_path]),
                'no-current' => $second->update(['current_version_id' => null]),
                'foreign' => $second->update(['current_version_id' => $profile->photos()->first()->current_version_id]),
            };
            $response = $this->get('/modelos/mia')->assertOk();
            $this->assertCount(1, $response->viewData('publicProfile')->photos, $case);
            $response->assertDontSee($version->public_token, false)->assertDontSee($history->public_token, false);
        }
        $this->get('/modelos/fotos/'.$history->public_token)->assertNotFound();
    }

    public function test_equal_positions_use_id_order_and_photo_count_does_not_add_queries(): void
    {
        $profile = $this->publicProfile();
        Model::preventLazyLoading();
        try {
            DB::enableQueryLog();
            $one = $this->get('/modelos/mia')->assertOk();
            $oneQueries = DB::getQueryLog();
            DB::disableQueryLog();
            $expected = [$one->viewData('publicProfile')->primaryPhoto['url']];
            for ($position = 1; $position < config('model-photos.max_photos'); $position++) {
                $photo = $this->publicPhoto($profile, 0);
                $expected[] = route('public.models.photos.show', ['publicToken' => $photo->currentVersion->public_token]);
            }
            DB::flushQueryLog();
            DB::enableQueryLog();
            $many = $this->get('/modelos/mia')->assertOk();
            $manyQueries = DB::getQueryLog();
            $this->assertSame($expected, array_column($many->viewData('publicProfile')->photos, 'url'));
            $this->assertCount(count($oneQueries), $manyQueries);
            foreach ($manyQueries as $query) {
                $this->assertStringNotContainsString('original_path', $query['query']);
                $this->assertStringNotContainsString('thumbnail_path', $query['query']);
                $this->assertStringNotContainsString('max(', strtolower($query['query']));
            }
        } finally {
            DB::disableQueryLog();
            Model::preventLazyLoading(false);
        }
    }
}
