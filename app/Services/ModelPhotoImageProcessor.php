<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;
use Throwable;

class ModelPhotoImageProcessor
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = ImageManager::gd();
    }

    /**
     * @return array{contents:array{original:string, processed:string, public:string, thumbnail:string}, public_watermarked:bool, mime_type:string, extension:string, original_name:?string, file_size:int, width:int, height:int, processed_width:int, processed_height:int}
     */
    public function process(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $mime = $path ? (new \finfo(FILEINFO_MIME_TYPE))->file($path) : false;

        if (! is_string($mime) || ! in_array($mime, config('model-photos.allowed_mimes'), true)) {
            throw ValidationException::withMessages(['photo' => 'El archivo debe ser una imagen JPEG, PNG o WebP válida.']);
        }

        if ((int) $file->getSize() > ((int) config('model-photos.max_size_kb') * 1024)) {
            throw ValidationException::withMessages(['photo' => 'La fotografía no puede superar los 10 MB.']);
        }

        $size = $path ? getimagesize($path) : false;
        if (! is_array($size)) {
            throw ValidationException::withMessages(['photo' => 'No pudimos leer la imagen enviada.']);
        }

        [$width, $height] = [$size[0], $size[1]];
        if ($width < config('model-photos.min_width')
            || $height < config('model-photos.min_height')
            || $width > config('model-photos.max_width')
            || $height > config('model-photos.max_height')) {
            throw ValidationException::withMessages(['photo' => 'La imagen no puede superar los 10000 px de ancho o alto. Se aceptan fotos verticales, horizontales o cuadradas.']);
        }

        try {
            $processed = $this->manager->read($path);
            $processed->scaleDown(
                width: (int) config('model-photos.processed_max_side'),
                height: (int) config('model-photos.processed_max_side'),
            );
            $processedContent = $processed->toWebp(quality: 88)->toString();

            $thumbnail = $this->manager->read($processedContent)->cover(
                (int) config('model-photos.thumbnail.width'),
                (int) config('model-photos.thumbnail.height'),
                'center',
            );
            $thumbnailContent = $thumbnail->toWebp(quality: 86)->toString();

            $public = $this->publicVariant($processedContent);
        } catch (Throwable $exception) {
            throw ValidationException::withMessages(['photo' => 'No pudimos procesar la imagen enviada.']);
        }

        return [
            'contents' => [
                'original' => $file->getContent(),
                'processed' => $processedContent,
                'public' => $public['contents'],
                'thumbnail' => $thumbnailContent,
            ],
            'public_watermarked' => $public['watermarked'],
            'mime_type' => $mime,
            'extension' => match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                default => 'webp',
            },
            'original_name' => basename((string) $file->getClientOriginalName()),
            'file_size' => (int) $file->getSize(),
            'width' => $width,
            'height' => $height,
            'processed_width' => $processed->width(),
            'processed_height' => $processed->height(),
        ];
    }

    /** @return array{contents:string, watermarked:bool} */
    public function publicVariant(string $processedContent): array
    {
        $public = $this->manager->read($processedContent);
        $unmarkedContent = $public->toWebp(quality: 88)->toString();
        if (config('model-photos.watermark.enabled')) {
            $watermarkAsset = (string) config('model-photos.watermark.asset');
            $watermarkPath = str_starts_with($watermarkAsset, 'public/')
                ? base_path($watermarkAsset)
                : public_path($watermarkAsset);
            $watermark = $this->manager->read($watermarkPath);
            $watermark->scaleDown(width: max(1, (int) round($public->width() * (float) config('model-photos.watermark.relative_width'))));
            $margin = (int) config('model-photos.watermark.margin');
            $public->place(
                $watermark,
                (string) config('model-photos.watermark.position'),
                $margin,
                $margin,
                (int) config('model-photos.watermark.opacity'),
            );
        }
        $publicContent = $public->toWebp(quality: 88)->toString();
        $size = getimagesizefromstring($publicContent);
        if (! is_array($size) || $size[2] !== IMAGETYPE_WEBP) {
            throw new \RuntimeException('Invalid public image.');
        }

        return ['contents' => $publicContent, 'watermarked' => (bool) config('model-photos.watermark.enabled') && $publicContent !== $unmarkedContent];
    }
}
