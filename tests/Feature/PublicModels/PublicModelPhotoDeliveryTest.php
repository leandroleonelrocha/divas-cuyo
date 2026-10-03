<?php

namespace Tests\Feature\PublicModels;

use App\Http\Controllers\PublicModelPhotoController;
use App\Services\ModelPhotoStorage;
use App\Services\PublicModelPhotoService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class PublicModelPhotoDeliveryTest extends TestCase
{
    use PublicModelProfileFixtures;

    public function test_only_public_bytes_are_delivered_with_private_headers(): void
    {
        $profile = $this->publicProfile();
        $version = $profile->photos()->first()->currentVersion;
        $url = '/modelos/fotos/'.$version->public_token;
        $response = $this->get($url.'?variant=original')->assertOk()
            ->assertHeader('Content-Type', 'image/webp')
            ->assertHeader('Content-Disposition', 'inline; filename="photo.webp"')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame(Storage::disk('model_photos')->get($version->public_path), $response->streamedContent());
        $response->assertHeaderMissing('ETag');
        $this->get($url.'/original')->assertNotFound();
    }

    public function test_invalid_or_unknown_tokens_and_hidden_profiles_have_generic_errors(): void
    {
        $profile = $this->publicProfile();
        $token = $profile->photos()->first()->currentVersion->public_token;
        $profile->update(['is_published' => false]);
        $expected = $this->get('/modelos/missing')->getContent();
        foreach ([$token, (string) Str::uuid(), '123', '..%2Fprocessed.webp'] as $value) {
            $response = $this->get('/modelos/fotos/'.$value)->assertNotFound()->assertHeaderMissing('Location');
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $this->assertSame($expected, $response->getContent());
        }
        $this->actingAs($profile->user)->get('/modelos/fotos/'.$token)->assertNotFound();
    }

    public function test_pending_rejected_historical_and_foreign_current_versions_are_denied(): void
    {
        $profile = $this->publicProfile();
        $photo = $profile->photos()->first();
        $version = $photo->currentVersion;
        foreach (['pending', 'rejected'] as $status) {
            $version->update(['status' => $status]);
            $this->get('/modelos/fotos/'.$version->public_token)->assertNotFound();
        }
        $version->update(['status' => 'approved']);
        $other = $this->publicPhoto($profile, 1);
        $photo->update(['current_version_id' => $other->current_version_id]);
        $this->get('/modelos/fotos/'.$version->public_token)->assertNotFound();
        $this->get('/modelos/fotos/'.$other->currentVersion->public_token)->assertNotFound();
    }

    public function test_missing_watermark_files_and_private_or_unsafe_paths_fail_closed(): void
    {
        $profile = $this->publicProfile();
        $version = $profile->photos()->first()->currentVersion;
        $url = '/modelos/fotos/'.$version->public_token;
        $path = $version->public_path;
        $version->forceFill(['public_watermarked_at' => null])->save();
        $this->get($url)->assertNotFound();
        $version->forceFill(['public_watermarked_at' => now()])->save();
        foreach (['../secret', $version->original_path, $version->processed_path, $version->thumbnail_path, 'model-profiles/missing/public.webp'] as $invalid) {
            $version->update(['public_path' => $invalid]);
            $this->get($url)->assertNotFound();
            $this->get('/modelos/mia')->assertNotFound();
        }
        $version->update(['public_path' => $path]);
        Storage::disk('model_photos')->delete($path);
        $this->get($url)->assertNotFound();
    }

    public function test_stream_is_opened_before_response_and_closed_after_delivery(): void
    {
        $profile = $this->publicProfile();
        $version = $profile->photos()->first()->currentVersion;
        $streams = [];
        $this->partialMock(ModelPhotoStorage::class, function ($mock) use (&$streams) {
            $mock->shouldReceive('readStream')->andReturnUsing(function () use (&$streams) {
                $stream = fopen('php://temp', 'w+b');
                fwrite($stream, 'PUBLIC_BYTES');
                rewind($stream);
                $streams[] = $stream;

                return $stream;
            });
        });
        $response = $this->get('/modelos/fotos/'.$version->public_token)->assertOk();
        $this->assertNotEmpty($streams);
        $this->assertSame('PUBLIC_BYTES', $response->streamedContent());
        foreach ($streams as $stream) {
            $this->assertFalse(is_resource($stream));
        }
    }

    public function test_secondary_urls_revalidate_each_publication_condition_and_deletion(): void
    {
        $profile = $this->publicProfile();
        $secondary = $this->publicPhoto($profile, 1);
        $url = '/modelos/fotos/'.$secondary->currentVersion->public_token;
        $this->get($url)->assertOk()->streamedContent();
        foreach (['identity_status' => 'pending', 'review_status' => 'rejected', 'is_published' => false] as $field => $value) {
            $before = $profile->$field;
            $profile->forceFill([$field => $value])->save();
            $this->get($url)->assertNotFound();
            $profile->forceFill([$field => $before])->save();
        }
        $profile->user->forceFill(['email_verified_at' => null])->save();
        $this->get($url)->assertNotFound();
        $profile->user->forceFill(['email_verified_at' => now()])->save();
        $this->get($url)->assertOk()->streamedContent();
        $secondary->delete();
        $this->get($url)->assertNotFound();
    }

    public function test_read_failure_and_empty_secondary_return_generic_error_without_fallback(): void
    {
        $profile = $this->publicProfile();
        $secondary = $this->publicPhoto($profile, 1)->currentVersion;
        $url = '/modelos/fotos/'.$secondary->public_token;
        Storage::disk('model_photos')->put($secondary->public_path, '');
        $this->get($url)->assertNotFound();
        $this->partialMock(ModelPhotoStorage::class, function ($mock) {
            $mock->shouldReceive('readStream')->andThrow(new \RuntimeException('PRIVATE_STORAGE_PATH'));
        });
        $this->get($url)->assertNotFound()->assertDontSee('PRIVATE_STORAGE_PATH');
    }

    public function test_stream_is_closed_and_only_safe_event_is_logged_when_delivery_fails(): void
    {
        $protocol = 'publicphotofailure';
        $this->assertTrue(stream_wrapper_register($protocol, FailingPublicPhotoStream::class));
        FailingPublicPhotoStream::$closed = false;
        $stream = fopen($protocol.'://image', 'rb');
        $photos = $this->mock(PublicModelPhotoService::class);
        $photos->shouldReceive('openByToken')->once()->andReturn($stream);
        Log::shouldReceive('warning')->once()->with('public_photo_stream_failed');

        try {
            $response = (new PublicModelPhotoController)('opaque-token', $photos);
            ob_start();
            try {
                $response->sendContent();
            } finally {
                $body = ob_get_clean();
            }
        } finally {
            stream_wrapper_unregister($protocol);
        }

        $this->assertSame('', $body);
        $this->assertTrue(FailingPublicPhotoStream::$closed);
    }
}

final class FailingPublicPhotoStream
{
    public static bool $closed = false;

    public $context;

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        return true;
    }

    public function stream_read(int $count): string
    {
        throw new \RuntimeException('PRIVATE_STREAM_CONTENT');
    }

    public function stream_eof(): bool
    {
        return false;
    }

    public function stream_stat(): array|false
    {
        return [];
    }

    public function stream_close(): void
    {
        self::$closed = true;
    }
}
