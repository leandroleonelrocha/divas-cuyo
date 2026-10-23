<?php

namespace Tests\Feature\ModelVideos;

use App\Jobs\DeleteModelVideoFiles;
use App\Jobs\ProcessModelVideo;
use App\Models\ModelVideo;
use App\Models\User;
use App\Services\ModelVideoProcessor;
use App\Services\ModelVideoService;
use App\Services\VideoHosting\VideoHostingManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class ModelVideoTest extends TestCase
{
    use PublicModelProfileFixtures;
    use \Tests\Support\AcceptsLegalDisclaimer;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('model_videos');
        Queue::fake();
    }

    private function video(User $owner, array $attributes = []): ModelVideo
    {
        $token = (string) Str::uuid();
        Storage::disk('model_videos')->put('originals/'.$token.'/upload', 'original');
        return $owner->modelProfile->videos()->create([
            'public_token' => $token, 'resource_id' => $token, 'provider' => 'local',
            'original_path' => 'originals/'.$token.'/upload', 'original_name' => 'video.mp4',
            'original_size' => 8, ...$attributes,
        ]);
    }

    private function ready(ModelVideo $video): ModelVideo
    {
        Storage::disk('model_videos')->put('hosted/'.$video->resource_id.'/video.mp4', '0123456789');
        Storage::disk('model_videos')->put('hosted/'.$video->resource_id.'/poster.jpg', 'poster');
        $video->update(['processing_status' => 'ready', 'watermarked_at' => now()]);
        return $video->fresh();
    }

    public function test_upload_uses_authenticated_profile_and_enforces_quota(): void
    {
        $owner = User::factory()->withCompletedRegistration()->create();
        $other = User::factory()->withCompletedRegistration()->create();
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($owner)->post(route('account.videos.store'), [
                'model_profile_id' => $other->modelProfile->id,
                'video' => UploadedFile::fake()->create('clip.mp4', 20, 'video/mp4'),
            ])->assertRedirect(route('account.videos.index'));
        }
        $this->assertSame(3, $owner->modelProfile->videos()->count());
        $this->assertSame(0, $other->modelProfile->videos()->count());
        Queue::assertPushed(ProcessModelVideo::class, 3);
        $this->actingAs($owner)->post(route('account.videos.store'), [
            'video' => UploadedFile::fake()->create('clip.mp4', 20, 'video/mp4'),
        ])->assertSessionHasErrors('video');
        $video = ModelVideo::firstOrFail();
        Storage::disk('model_videos')->assertExists($video->original_path);
        $this->assertFalse($video->ready());
    }

    public function test_invalid_uploads_and_unverified_users_are_rejected(): void
    {
        $owner = User::factory()->withCompletedRegistration()->create();
        foreach ([UploadedFile::fake()->create('large.mp4', 102401, 'video/mp4'), UploadedFile::fake()->image('image.jpg')] as $file) {
            $this->actingAs($owner)->post(route('account.videos.store'), ['video' => $file])->assertSessionHasErrors('video');
        }
        $owner->forceFill(['email_verified_at' => null])->save();
        $this->actingAs($owner)->post(route('account.videos.store'), [])->assertRedirect();
        $this->assertDatabaseCount('model_videos', 0);
    }

    public function test_private_delivery_and_deletion_require_owner_or_admin(): void
    {
        $owner = User::factory()->withCompletedRegistration()->create();
        $other = User::factory()->withCompletedRegistration()->create();
        $video = $this->ready($this->video($owner));
        $this->actingAs($owner)->get(route('account.videos.index'))->assertOk()->assertSee('Mis videos');
        $this->get(route('account.videos.file', [$video, 'video']))->assertOk();
        $this->get(route('account.videos.file', [$video, 'original']))->assertNotFound();
        $this->actingAs($other)->get(route('account.videos.file', [$video, 'poster']))->assertForbidden();
        $this->delete(route('account.videos.destroy', $video))->assertForbidden();
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get(route('admin.model-videos.file', [$video, 'video']))->assertOk();
        $this->actingAs($owner)->delete(route('account.videos.destroy', $video))->assertRedirect();
        Queue::assertPushed(DeleteModelVideoFiles::class);
        $this->get(route('account.videos.file', [$video, 'video']))->assertNotFound();
        (new DeleteModelVideoFiles($video->id))->handle(app(VideoHostingManager::class));
        Storage::disk('model_videos')->assertMissing($video->original_path);
        Storage::disk('model_videos')->assertMissing('hosted/'.$video->resource_id.'/video.mp4');
    }

    public function test_public_delivery_requires_approval_watermark_and_visible_profile_and_supports_ranges(): void
    {
        $profile = $this->publicProfile();
        $video = $this->ready($this->video($profile->user));
        $url = route('public.models.videos.file', [$video->public_token, 'video']);
        $this->get($url)->assertNotFound();
        $video->update(['moderation_status' => 'approved']);
        $this->get($url, ['Range' => 'bytes=2-5'])->assertStatus(206)->assertHeader('Content-Range', 'bytes 2-5/10');
        $this->get(route('public.models.show', $profile->slug))->assertOk()->assertSee('Videos de Mia')->assertSee($url, false);
        $video->update(['watermarked_at' => null]);
        $this->get($url)->assertNotFound();
        $video->update(['watermarked_at' => now()]);
        $profile->update(['is_published' => false]);
        $this->get($url)->assertNotFound();
        $this->get(route('public.models.videos.file', [$video->public_token, 'poster']))->assertNotFound();
    }

    public function test_moderation_checks_permissions_and_records_reviewer(): void
    {
        $owner = User::factory()->withCompletedRegistration()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $video = $this->ready($this->video($owner));
        $service = app(ModelVideoService::class);
        $this->assertFalse($owner->can('moderate', $video));
        $service->moderate($admin, $video, 'approved');
        $this->assertDatabaseHas('model_videos', ['id' => $video->id, 'moderation_status' => 'approved', 'reviewed_by' => $admin->id]);
        $service->moderate($admin, $video, 'rejected', 'Corregir el contenido');
        $this->assertDatabaseHas('model_videos', ['id' => $video->id, 'rejection_reason' => 'Corregir el contenido']);
        $this->expectException(ValidationException::class);
        $service->moderate($admin, $video, 'rejected', ' ');
    }

    public function test_unprocessed_video_cannot_be_approved(): void
    {
        $owner = User::factory()->withCompletedRegistration()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $this->expectException(ValidationException::class);
        app(ModelVideoService::class)->moderate($admin, $this->video($owner), 'approved');
    }

    public function test_processing_produces_private_assets_and_does_not_auto_approve(): void
    {
        $video = $this->video(User::factory()->withCompletedRegistration()->create());
        $processor = $this->mock(ModelVideoProcessor::class);
        $processor->shouldReceive('process')->once()->andReturnUsing(function ($source, $directory) {
            file_put_contents($directory.'/video.mp4', 'marked video');
            file_put_contents($directory.'/poster.jpg', 'marked poster');
            return ['duration' => 5, 'width' => 640, 'height' => 480];
        });
        $job = new ProcessModelVideo($video->id);
        $job->handle($processor, app(VideoHostingManager::class));
        $job->handle($processor, app(VideoHostingManager::class)); // Idempotent completed delivery.
        $this->assertTrue($video->fresh()->ready());
        $this->assertSame('pending', $video->fresh()->moderation_status);
        Storage::disk('model_videos')->assertExists('hosted/'.$video->resource_id.'/video.mp4');
    }

    public function test_deleted_during_processing_never_reappears_and_removes_uploaded_assets(): void
    {
        $video = $this->video(User::factory()->withCompletedRegistration()->create());
        $processor = $this->mock(ModelVideoProcessor::class);
        $processor->shouldReceive('process')->andReturnUsing(function ($source, $directory) use ($video) {
            $video->delete();
            file_put_contents($directory.'/video.mp4', 'marked video');
            file_put_contents($directory.'/poster.jpg', 'marked poster');
            return ['duration' => 5, 'width' => 640, 'height' => 480];
        });
        (new ProcessModelVideo($video->id))->handle($processor, app(VideoHostingManager::class));
        $this->assertNull(ModelVideo::find($video->id));
        Storage::disk('model_videos')->assertMissing('hosted/'.$video->resource_id.'/video.mp4');
    }

    public function test_failed_processing_can_be_retried_by_owner_only(): void
    {
        $owner = User::factory()->withCompletedRegistration()->create();
        $video = $this->video($owner);
        (new ProcessModelVideo($video->id))->failed(new \RuntimeException('Internal path'));
        $this->assertSame('failed', $video->fresh()->processing_status);
        $this->assertStringNotContainsString('Internal path', $video->fresh()->error_message);
        $this->actingAs($owner)->post(route('account.videos.retry', $video))->assertRedirect();
        Queue::assertPushed(ProcessModelVideo::class);
        $this->assertSame('queued', $video->fresh()->processing_status);
        $this->post(route('account.videos.retry', $video))->assertStatus(409);
    }

    public function test_admin_relation_manager_renders_and_approves_video(): void
    {
        $owner = User::factory()->withCompletedRegistration()->create();
        $video = $this->ready($this->video($owner));
        $admin = User::factory()->withCompletedRegistration()->create(['is_admin' => true]);
        $this->actingAs($admin);
        \Livewire\Livewire::test(\App\Filament\Resources\ModelProfiles\RelationManagers\ModelVideosRelationManager::class, [
            'ownerRecord' => $owner->modelProfile,
            'pageClass' => \App\Filament\Resources\ModelProfiles\Pages\ViewModelProfile::class,
        ])->assertSuccessful()->assertSee('Pendiente de aprobación')
            ->callTableAction('approve', $video)->assertHasNoTableActionErrors();
        $this->assertSame('approved', $video->fresh()->moderation_status);
    }

    public function test_migration_preserves_original_host_until_copy_is_available_and_delete_cleans_both(): void
    {
        config(['model-videos.providers.second' => \App\Services\VideoHosting\LocalVideoHostingProvider::class]);
        $owner = User::factory()->withCompletedRegistration()->create();
        $video = $this->ready($this->video($owner));
        $oldResource = $video->resource_id;
        $this->artisan('model-videos:migrate-hosting', ['video' => $video->id, 'provider' => 'second'])->assertSuccessful();
        $video->refresh();
        $this->assertSame('second', $video->provider);
        $this->assertNotSame($oldResource, $video->resource_id);
        $this->assertSame($oldResource, $video->hosting_history[0]['resource_id']);
        Storage::disk('model_videos')->assertExists('hosted/'.$oldResource.'/video.mp4');
        Storage::disk('model_videos')->assertExists('hosted/'.$video->resource_id.'/video.mp4');
        app(ModelVideoService::class)->delete($owner, $video);
        (new DeleteModelVideoFiles($video->id))->handle(app(VideoHostingManager::class));
        Storage::disk('model_videos')->assertMissing('hosted/'.$oldResource.'/video.mp4');
        Storage::disk('model_videos')->assertMissing('hosted/'.$video->resource_id.'/video.mp4');
    }

    public function test_processing_failure_never_exposes_partial_assets(): void
    {
        $owner = User::factory()->withCompletedRegistration()->create();
        $video = $this->video($owner);
        $processor = $this->mock(ModelVideoProcessor::class);
        $processor->shouldReceive('process')->once()->andThrow(new \RuntimeException('Invalid video'));
        try {
            (new ProcessModelVideo($video->id))->handle($processor, app(VideoHostingManager::class));
            $this->fail('Processing should fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Invalid video', $exception->getMessage());
        }
        $this->assertFalse($video->fresh()->ready());
        $this->actingAs($owner)->get(route('account.videos.file', [$video, 'video']))->assertNotFound();
        $this->assertDirectoryDoesNotExist(storage_path('app/private/model-videos/work/'.$video->public_token));
    }

    public function test_failed_host_migration_keeps_previous_reference_and_removes_partial_copy(): void
    {
        config(['model-videos.providers.unavailable' => UnavailableModelVideoProvider::class]);
        $video = $this->ready($this->video(User::factory()->withCompletedRegistration()->create()));
        $resource = $video->resource_id;
        try {
            $this->artisan('model-videos:migrate-hosting', ['video' => $video->id, 'provider' => 'unavailable']);
            $this->fail('An unavailable provider must not replace the current host.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('El proveedor no confirmó disponibilidad.', $exception->getMessage());
        }
        $this->assertSame('local', $video->fresh()->provider);
        $this->assertSame($resource, $video->fresh()->resource_id);
        $this->assertCount(2, Storage::disk('model_videos')->allFiles('hosted'));
    }
}


class UnavailableModelVideoProvider extends \App\Services\VideoHosting\LocalVideoHostingProvider
{
    public function available(string $resourceId): bool
    {
        return false;
    }
}
