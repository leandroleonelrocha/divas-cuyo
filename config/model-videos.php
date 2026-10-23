<?php

use App\Services\VideoHosting\LocalVideoHostingProvider;

return [
    'provider' => env('MODEL_VIDEOS_PROVIDER', 'local'),
    'providers' => ['local' => LocalVideoHostingProvider::class],
    'max_videos' => 3,
    'max_size_kb' => 102400,
    'max_duration' => 60,
    'max_side' => 1280,
    'ffmpeg' => env('FFMPEG_BINARY', 'ffmpeg'),
    'ffprobe' => env('FFPROBE_BINARY', 'ffprobe'),
    'queue_connection' => env('MODEL_VIDEOS_QUEUE_CONNECTION', 'video_database'),
    'watermark' => [
        'asset' => 'public/images/watermark-divas-cuyo.png',
        'relative_width' => 0.35,
        'opacity' => 0.38,
        'position' => 'center',
        'margin' => 24,
    ],
];
