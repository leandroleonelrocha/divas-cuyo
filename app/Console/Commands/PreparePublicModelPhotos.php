<?php

namespace App\Console\Commands;

use App\Models\ModelPhoto;
use App\Models\ModelPhotoVersion;
use App\Services\ModelPhotoImageProcessor;
use App\Services\ModelPhotoStorage;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class PreparePublicModelPhotos extends Command
{
    protected $signature = 'model-photos:prepare-public {--dry-run : Contar sin escribir}';

    protected $description = 'Preparar variantes públicas con watermark de fotos vigentes aprobadas';

    public function handle(ModelPhotoImageProcessor $processor, ModelPhotoStorage $storage): int
    {
        $candidates = ModelPhotoVersion::query()->where('status', 'approved')->whereNull('public_watermarked_at')
            ->whereHas('photo', fn (Builder $photo) => $photo->whereColumn('model_photos.current_version_id', 'model_photo_versions.id'));
        if ($this->option('dry-run')) {
            $this->info('Versiones por preparar: '.$candidates->count());

            return self::SUCCESS;
        }

        $prepared = 0;
        $failed = 0;
        $candidates->chunkById(100, function ($versions) use ($processor, $storage, &$prepared, &$failed): void {
            foreach ($versions as $version) {
                $newPath = null;
                $persisted = false;
                try {
                    if (! $storage->isSafePath($version->processed_path) || basename($version->processed_path) !== 'processed.webp') {
                        throw new RuntimeException('Invalid preparation source.');
                    }
                    $source = $storage->disk()->get($version->processed_path);
                    if (! is_string($source) || $source === '') {
                        throw new RuntimeException('Missing preparation source.');
                    }
                    $public = $processor->publicVariant($source);
                    if (! $public['watermarked']) {
                        throw new RuntimeException('Watermark unavailable.');
                    }
                    $newPath = $storage->storePublicVariant($version->processed_path, $public['contents']);
                    DB::transaction(function () use ($version, $newPath, $storage): void {
                        $photo = ModelPhoto::query()->lockForUpdate()->find($version->model_photo_id);
                        $locked = ModelPhotoVersion::query()->lockForUpdate()->find($version->id);
                        if (! $photo || ! $locked || $photo->current_version_id !== $locked->id
                            || $locked->model_photo_id !== $photo->id || $locked->status->value !== 'approved'
                            || $locked->public_watermarked_at !== null || $locked->processed_path !== $version->processed_path) {
                            throw new RuntimeException('Preparation candidate changed.');
                        }
                        $oldPath = $locked->public_path;
                        $locked->forceFill(['public_path' => $newPath, 'public_watermarked_at' => now()])->save();
                        DB::afterCommit(function () use ($storage, $oldPath): void {
                            try {
                                if ($storage->isPublicPath($oldPath)) {
                                    $storage->deletePaths([$oldPath]);
                                }
                            } catch (Throwable) {
                                Log::warning('public_photo_old_variant_cleanup_failed');
                            }
                        });
                    }, 3);
                    $persisted = true;
                    $prepared++;
                } catch (Throwable) {
                    $failed++;
                    Log::warning('public_photo_preparation_failed', ['version_id' => $version->id]);
                } finally {
                    if ($newPath !== null && ! $persisted) {
                        $storage->deletePaths([$newPath]);
                    }
                }
            }
        });

        $this->info("Preparadas: {$prepared}. Fallidas: {$failed}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
