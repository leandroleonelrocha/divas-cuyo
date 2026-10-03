<?php

namespace App\Services;

use App\Enums\ModelPhotoVersionStatus;
use App\Models\ModelProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PublicModelProfileVisibility
{
    public function __construct(private readonly ModelPhotoStorage $storage) {}

    /**
     * Database candidates only: callers must also use publiclyVisible before presentation.
     *
     * @return Builder<ModelProfile>
     */
    public function candidates(): Builder
    {
        return ModelProfile::query()
            ->select(['id', 'slug', 'stage_name', 'availability_status', 'province_id', 'locality_id', 'approximate_location_text'])
            ->whereNotNull('slug')
            ->whereNotNull('stage_name')
            ->where('stage_name', '!=', '')
            ->where('identity_status', 'approved')
            ->where('review_status', 'approved')
            ->where('is_published', true)
            ->whereHas('user', fn (Builder $query) => $query->whereNotNull('email_verified_at'))
            ->whereHas('photos', fn (Builder $query) => $query
                ->where('is_primary', true)
                ->whereHas('currentVersion', fn (Builder $version) => $version
                    ->where('status', 'approved')
                    ->whereNotNull('public_watermarked_at')
                    ->whereNotNull('public_token')
                    ->whereColumn('model_photo_versions.model_photo_id', 'model_photos.id')));
    }

    public function publiclyVisible(ModelProfile $profile): bool
    {
        // Query persisted state, not a possibly stale or user-supplied model snapshot.
        $candidate = $this->candidates()->whereKey($profile->getKey())
            ->with(['photos' => fn (HasMany $query) => $query
                ->select(['id', 'model_profile_id', 'current_version_id'])
                ->where('is_primary', true)
                ->with('currentVersion:id,model_photo_id,status,public_path,public_watermarked_at,public_token')])
            ->first();

        if (! $candidate || trim($candidate->stage_name) === '' || $candidate->photos->count() !== 1) {
            return false;
        }

        $photo = $candidate->photos->first();
        $version = $photo->currentVersion;
        if (! $version || $version->model_photo_id !== $photo->id
            || $version->status !== ModelPhotoVersionStatus::Approved || $version->public_watermarked_at === null
            || ! Str::isUuid($version->public_token ?? '')) {
            return false;
        }

        $stream = $this->storage->openPublicStream($version->public_path ?? '');
        if (! is_resource($stream)) {
            return false;
        }
        fclose($stream);

        return true;
    }
}
