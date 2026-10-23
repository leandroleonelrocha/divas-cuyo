<?php

namespace App\Jobs;

use App\Models\ModelVideo;
use App\Services\ModelVideoProcessor;
use App\Services\VideoHosting\VideoHostingManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessModelVideo implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 900;
    public array $backoff = [30, 120];

    public function __construct(public int $videoId)
    {
        $this->onConnection(config('model-videos.queue_connection'))->onQueue('videos');
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('model-video:'.$this->videoId))->releaseAfter(30)->expireAfter(930)];
    }

    public function handle(ModelVideoProcessor $processor, VideoHostingManager $hosting): void
    {
        $video = ModelVideo::find($this->videoId);
        if (! $video || $video->ready()) {
            return;
        }
        $provider = $hosting->provider($video->provider);
        $directory = storage_path('app/private/model-videos/work/'.$video->public_token);
        File::ensureDirectoryExists($directory, 0700);
        $video->update(['processing_status' => 'processing', 'error_message' => null]);
        try {
            $metadata = $processor->process(Storage::disk('model_videos')->path($video->original_path), $directory);
            $provider->store($video->resource_id, $directory.'/video.mp4', $directory.'/poster.jpg');
            if (! $provider->available($video->resource_id)) {
                throw new \RuntimeException('El proveedor no confirmó los archivos.');
            }
            $published = DB::transaction(function () use ($video, $metadata): bool {
                $current = ModelVideo::query()->lockForUpdate()->find($video->id);
                if (! $current) {
                    return false;
                }
                $current->update([...$metadata, 'processing_status' => 'ready', 'watermarked_at' => now(), 'error_message' => null]);

                return true;
            });
            if (! $published) {
                $provider->delete($video->resource_id);
            }
        } catch (Throwable $exception) {
            ModelVideo::whereKey($video->id)->update(['processing_status' => 'queued']);
            if (! ModelVideo::whereKey($video->id)->exists()) {
                $provider->delete($video->resource_id);
                return;
            }
            throw $exception;
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $video = ModelVideo::withTrashed()->find($this->videoId);
        if (! $video || $video->ready()) {
            return;
        }
        ModelVideo::whereKey($this->videoId)->update([
            'processing_status' => 'failed',
            'error_message' => 'No pudimos procesar el video. Verificá el formato y la duración o intentá nuevamente.',
        ]);
        File::deleteDirectory(storage_path('app/private/model-videos/work/'.$video->public_token));
        try {
            app(VideoHostingManager::class)->provider($video->provider)->delete($video->resource_id);
        } catch (Throwable $cleanupException) {
            report($cleanupException);
        }
    }
}
