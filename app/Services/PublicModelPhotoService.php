<?php

namespace App\Services;

use App\Enums\ModelPhotoVersionStatus;
use App\Models\ModelPhoto;
use App\Models\ModelPhotoVersion;
use App\Models\ModelProfile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class PublicModelPhotoService
{
    public const VERSION_COLUMNS = 'id,model_photo_id,status,public_token,public_path,public_watermarked_at,processed_width,processed_height';

    public function __construct(private readonly ModelPhotoStorage $storage) {}

    public function openByToken(string $token)
    {
        if (! Str::isUuid($token)) {
            return null;
        }
        $version = ModelPhotoVersion::query()->select(explode(',', self::VERSION_COLUMNS))
            ->where('public_token', $token)->with('photo:id,model_profile_id,current_version_id')->first();
        $photo = $version?->photo;
        if (! $photo || ! $this->currentPublicVersion($photo, $version)) {
            return null;
        }
        $profile = new ModelProfile;
        $profile->setAttribute('id', $photo->model_profile_id);
        if (Gate::denies('viewPublicModelProfile', $profile)) {
            return null;
        }

        return $this->storage->openPublicStream($version->public_path ?? '');
    }

    /** Project already-authorized profile photos without fetching histories or private variants. */
    public function gallery(ModelProfile $profile): array
    {
        $photos = [];
        $primary = null;
        foreach ($profile->currentApprovedPhotos as $photo) {
            $version = $photo->currentVersion;
            if (! $version || ! $this->currentPublicVersion($photo, $version)) {
                continue;
            }
            $stream = $this->storage->openPublicStream($version->public_path ?? '');
            if (! is_resource($stream)) {
                continue;
            }
            fclose($stream);
            $projection = [
                'url' => route('public.models.photos.show', ['publicToken' => $version->public_token]),
                'alt' => 'Foto de '.$profile->stage_name,
                'position' => $photo->position,
                'width' => $version->processed_width,
                'height' => $version->processed_height,
            ];
            $photos[] = $projection;
            if ($photo->is_primary) {
                $primary = $projection;
            }
        }

        return ['primary' => $primary, 'photos' => $photos];
    }

    private function currentPublicVersion(ModelPhoto $photo, ModelPhotoVersion $version): bool
    {
        return $photo->current_version_id === $version->id
            && $version->model_photo_id === $photo->id
            && $version->status === ModelPhotoVersionStatus::Approved
            && $version->public_watermarked_at !== null
            && Str::isUuid($version->public_token ?? '');
    }
}
