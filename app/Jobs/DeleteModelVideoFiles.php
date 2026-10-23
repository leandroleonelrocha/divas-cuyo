<?php

namespace App\Jobs;

use App\Models\ModelVideo;
use App\Services\VideoHosting\VideoHostingManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class DeleteModelVideoFiles implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public int $backoff = 60;

    public function __construct(public int $videoId)
    {
        $this->onConnection(config('model-videos.queue_connection'))->onQueue('videos');
    }

    public function handle(VideoHostingManager $hosting): void
    {
        $video = ModelVideo::onlyTrashed()->find($this->videoId);
        if (! $video) {
            return;
        }
        if ($video->resource_id) {
            $hosting->provider($video->provider)->delete($video->resource_id);
        }
        foreach ($video->hosting_history ?? [] as $copy) {
            $hosting->provider($copy['provider'])->delete($copy['resource_id']);
        }
        if ($video->original_path) {
            Storage::disk('model_videos')->delete($video->original_path);
        }
    }
}
