<?php

namespace App\Console\Commands;

use App\Models\ModelVideo;
use App\Services\VideoHosting\VideoHostingManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MigrateModelVideoHosting extends Command
{
    protected $signature = 'model-videos:migrate-hosting {video : ID del video} {provider : Proveedor configurado}';

    protected $description = 'Copia un video marcado y su portada, verifica disponibilidad y cambia el alojamiento conservando la copia anterior';

    public function handle(VideoHostingManager $hosting): int
    {
        $video = ModelVideo::findOrFail($this->argument('video'));
        if (! $video->ready()) {
            $this->error('El video todavía no está listo.');

            return self::FAILURE;
        }
        $targetName = $this->argument('provider');
        if ($video->provider === $targetName) {
            $this->info('El video ya usa ese proveedor.');

            return self::SUCCESS;
        }
        $target = $hosting->provider($targetName);
        $resource = (string) Str::uuid();
        $directory = storage_path('app/private/model-videos/migrations/'.$resource);
        File::ensureDirectoryExists($directory, 0700);
        $switched = false;
        try {
            $hosting->provider($video->provider)->export($video->resource_id, $directory);
            $target->store($resource, $directory.'/video.mp4', $directory.'/poster.jpg');
            if (! $target->available($resource)) {
                throw new \RuntimeException('El proveedor no confirmó disponibilidad.');
            }
            DB::transaction(function () use ($video, $targetName, $resource): void {
                $current = ModelVideo::whereKey($video->id)->lockForUpdate()->firstOrFail();
                if ($current->provider !== $video->provider || $current->resource_id !== $video->resource_id) {
                    throw new \RuntimeException('El alojamiento cambió durante la migración. Volvé a intentar.');
                }
                $current->update([
                    'provider' => $targetName, 'resource_id' => $resource,
                    'hosting_history' => [...($current->hosting_history ?? []), ['provider' => $video->provider, 'resource_id' => $video->resource_id]],
                ]);
            });
            $switched = true;
            $this->info('Alojamiento actualizado. La copia anterior se conserva en el historial y se eliminará al borrar el video.');

            return self::SUCCESS;
        } finally {
            File::deleteDirectory($directory);
            if (! $switched) {
                $target->delete($resource);
            }
        }
    }
}
