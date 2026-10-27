<?php

namespace App\Services;

use App\Enums\ModelNotificationType;
use App\Jobs\DeleteModelVideoFiles;
use App\Jobs\ProcessModelVideo;
use App\Models\ModelProfile;
use App\Models\ModelVideo;
use App\Models\User;
use App\Services\VideoHosting\VideoHostingManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ModelVideoService
{
    public function upload(User $user, UploadedFile $file): ModelVideo
    {
        abort_unless($user->hasVerifiedEmail(), 403);
        $profile = $user->modelProfile()->firstOrFail();
        $provider = config('model-videos.provider');
        app(VideoHostingManager::class)->provider($provider);
        $token = (string) Str::uuid();
        $path = null;
        try {
            $video = DB::transaction(function () use ($profile, $file, $provider, $token, &$path): ModelVideo {
                ModelProfile::whereKey($profile->id)->lockForUpdate()->firstOrFail();
                if ($profile->videos()->count() >= config('model-videos.max_videos')) {
                    throw ValidationException::withMessages(['video' => 'Alcanzaste el máximo de videos por perfil.']);
                }
                $path = $file->storeAs('originals/'.$token, 'upload', 'model_videos');

                return $profile->videos()->create([
                    'public_token' => $token, 'resource_id' => $token, 'provider' => $provider,
                    'original_path' => $path, 'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                    'original_size' => $file->getSize(), 'position' => (int) $profile->videos()->max('position') + 1,
                ]);
            });
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('model_videos')->delete($path);
            }
            throw $exception;
        }
        try {
            ProcessModelVideo::dispatch($video->id)->afterCommit();
        } catch (Throwable $exception) {
            $video->update(['processing_status' => 'failed', 'error_message' => 'No se pudo iniciar el procesamiento. Intentá nuevamente.']);
            report($exception);
        }

        return $video;
    }

    public function delete(User $user, ModelVideo $video): void
    {
        Gate::forUser($user)->authorize('delete', $video);
        DB::transaction(function () use ($video): void {
            $current = ModelVideo::whereKey($video->id)->lockForUpdate()->firstOrFail();
            $current->delete();
            DeleteModelVideoFiles::dispatch($current->id)->afterCommit();
        });
    }

    public function retry(User $user, ModelVideo $video): void
    {
        Gate::forUser($user)->authorize('delete', $video);
        DB::transaction(function () use ($video): void {
            $current = ModelVideo::whereKey($video->id)->lockForUpdate()->firstOrFail();
            abort_unless($current->processing_status === 'failed', 409);
            $current->update(['processing_status' => 'queued', 'error_message' => null]);
            ProcessModelVideo::dispatch($current->id)->afterCommit();
        });
    }

    public function moderate(User $reviewer, ModelVideo $video, string $status, ?string $reason = null): void
    {
        Gate::forUser($reviewer)->authorize('moderate', $video);
        if (! in_array($status, ['approved', 'rejected'], true) || ($status === 'rejected' && blank($reason))) {
            throw ValidationException::withMessages(['reason' => 'Indicá un motivo para rechazar el video.']);
        }
        DB::transaction(function () use ($video, $reviewer, $status, $reason): void {
            $current = ModelVideo::whereKey($video->id)->lockForUpdate()->firstOrFail();
            if (! $current->ready() || ! app(VideoHostingManager::class)->provider($current->provider)->available($current->resource_id)) {
                throw ValidationException::withMessages(['video' => 'El video con marca de agua todavía no está disponible.']);
            }
            $current->update([
                'moderation_status' => $status, 'rejection_reason' => $status === 'rejected' ? trim($reason) : null,
                'reviewed_by' => $reviewer->id, 'reviewed_at' => now(),
            ]);

            app(ModelNotificationService::class)->record(
                $current->modelProfile,
                $status === 'approved' ? ModelNotificationType::VideoApproved : ModelNotificationType::VideoRejected,
                $reason,
            );
        });
    }
}
