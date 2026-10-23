<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class ModelVideoProcessor
{
    private function run(array $arguments, int $timeout = 60): string
    {
        $process = new Process($arguments);
        $process->setTimeout($timeout);
        $process->mustRun();

        return $process->getOutput();
    }

    public function probe(string $path): array
    {
        return json_decode($this->run([
            config('model-videos.ffprobe'), '-v', 'error', '-protocol_whitelist', 'file,pipe',
            '-show_format', '-show_streams', '-of', 'json', $path,
        ]), true, flags: JSON_THROW_ON_ERROR);
    }

    /** Produces a burned-in watermark and a poster extracted from that watermarked output. */
    public function process(string $source, string $directory): array
    {
        $metadata = $this->probe($source);
        $stream = collect($metadata['streams'] ?? [])->firstWhere('codec_type', 'video');
        $duration = (float) ($metadata['format']['duration'] ?? 0);
        if (! $stream || ! is_finite($duration) || $duration <= 0 || $duration > config('model-videos.max_duration')
            || ! str_contains($metadata['format']['format_name'] ?? '', 'mp4')
            || ($stream['width'] ?? 0) < 2 || ($stream['height'] ?? 0) < 2
            || $stream['width'] > 8192 || $stream['height'] > 8192) {
            throw new RuntimeException('El archivo debe ser MP4 o MOV válido y respetar la duración máxima.');
        }

        $watermark = base_path(config('model-videos.watermark.asset'));
        if (! is_file($watermark)) {
            throw new RuntimeException('No está disponible la marca de agua.');
        }
        $width = (int) $stream['width'];
        $height = (int) $stream['height'];
        $rotation = (int) ($stream['tags']['rotate'] ?? 0);
        foreach ($stream['side_data_list'] ?? [] as $data) {
            $rotation = (int) ($data['rotation'] ?? $rotation);
        }
        if (abs($rotation) % 180 === 90) {
            [$width, $height] = [$height, $width];
        }
        $ratio = min(1, config('model-videos.max_side') / max($width, $height));
        $width = max(2, (int) floor($width * $ratio / 2) * 2);
        $height = max(2, (int) floor($height * $ratio / 2) * 2);
        $logoWidth = max(2, (int) ($width * max(0.05, min(0.8, (float) config('model-videos.watermark.relative_width')))));
        $opacity = max(0.05, min(1, (float) config('model-videos.watermark.opacity')));
        $margin = max(0, (int) config('model-videos.watermark.margin'));
        $position = match (config('model-videos.watermark.position')) {
            'bottom-right' => "max(0,W-w-$margin):max(0,H-h-$margin)",
            'bottom-left' => "$margin:max(0,H-h-$margin)",
            'top-right' => "max(0,W-w-$margin):$margin",
            'top-left' => "$margin:$margin",
            default => '(W-w)/2:(H-h)/2',
        };
        $filter = "[0:v:0]scale=$width:$height,setsar=1[base];[1:v:0]scale=$logoWidth:-1,format=rgba,colorchannelmixer=aa={$opacity}[logo];[base][logo]overlay=$position:format=auto,format=yuv420p[out]";
        $output = $directory.'/video.mp4';
        $this->run([
            config('model-videos.ffmpeg'), '-nostdin', '-y', '-v', 'error', '-threads', '2',
            '-protocol_whitelist', 'file,pipe', '-i', $source, '-i', $watermark,
            '-filter_complex_threads', '1', '-filter_complex', $filter, '-map', '[out]', '-map', '0:a:0?',
            '-map_metadata', '-1', '-map_chapters', '-1', '-c:v', 'libx264', '-threads', '2',
            '-preset', 'veryfast', '-crf', '23', '-r', '30', '-c:a', 'aac', '-b:a', '128k',
            '-movflags', '+faststart', '-t', (string) config('model-videos.max_duration'), $output,
        ], 720);
        $this->run([
            config('model-videos.ffmpeg'), '-nostdin', '-y', '-v', 'error', '-i', $output,
            '-frames:v', '1', '-update', '1', $directory.'/poster.jpg',
        ]);
        if (! is_file($output) || filesize($output) === 0 || ! is_file($directory.'/poster.jpg')) {
            throw new RuntimeException('No se pudo generar el video con marca de agua.');
        }

        return compact('duration', 'width', 'height');
    }
}
