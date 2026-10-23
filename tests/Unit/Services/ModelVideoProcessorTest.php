<?php

namespace Tests\Unit\Services;

use App\Services\ModelVideoProcessor;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ModelVideoProcessorTest extends TestCase
{
    public function test_real_transcoding_burns_watermark_into_video_and_poster(): void
    {
        if (! (new ExecutableFinder)->find(config('model-videos.ffmpeg')) || ! (new ExecutableFinder)->find(config('model-videos.ffprobe'))) {
            $this->markTestSkipped('FFmpeg y ffprobe son necesarios para esta prueba de integración.');
        }
        $directory = sys_get_temp_dir().'/model-video-test-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($directory);
        try {
            (new Process([config('model-videos.ffmpeg'), '-y', '-v', 'error', '-f', 'lavfi', '-i', 'color=c=blue:s=360x640:d=1', '-c:v', 'libx264', '-pix_fmt', 'yuv420p', $directory.'/source.mp4']))->mustRun();
            $processor = app(ModelVideoProcessor::class);
            $result = $processor->process($directory.'/source.mp4', $directory);
            $this->assertSame(360, $result['width']);
            $this->assertSame(640, $result['height']);
            $this->assertGreaterThan(0, filesize($directory.'/video.mp4'));
            $this->assertSame('h264', $processor->probe($directory.'/video.mp4')['streams'][0]['codec_name']);
            // A plain blue source has no red or green detail. The logo must be visible in its decoded poster.
            $poster = imagecreatefromjpeg($directory.'/poster.jpg');
            $markedPixels = 0;
            for ($y = 0; $y < imagesy($poster); $y++) {
                for ($x = 0; $x < imagesx($poster); $x++) {
                    $pixel = imagecolorat($poster, $x, $y);
                    if ((($pixel >> 16) & 255) > 25 || (($pixel >> 8) & 255) > 25) {
                        $markedPixels++;
                    }
                }
            }
            imagedestroy($poster);
            $this->assertGreaterThan(100, $markedPixels, 'La portada extraída del video debe contener la marca de agua.');
            config(['model-videos.max_duration' => 0.5]);
            $this->expectException(\RuntimeException::class);
            $processor->process($directory.'/source.mp4', $directory);
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
