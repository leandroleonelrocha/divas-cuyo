<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ModelProfiles\ModelProfileResource;
use App\Models\ModelProfile;
use App\Services\AccountEnrollmentState;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class PendingContent extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    protected int|array|null $columns = 1;

    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return auth()->user()?->isVerifiedAdmin() ?? false;
    }

    public static function filter(Builder $query): Builder
    {
        return $query->where('identity_status', 'approved')->where('review_status', 'approved')
            ->whereHas('user', fn (Builder $user) => $user
                ->where('is_admin', false)
                ->where('profile_completion_status', AccountEnrollmentState::ACTIVE))
            ->where(fn (Builder $content) => $content
                ->whereHas('photos.latestVersion', fn (Builder $version) => $version->where('status', 'pending'))
                ->orWhereHas('bios', fn (Builder $bio) => $bio->where('status', 'pending'))
                ->orWhereHas('videos', fn (Builder $video) => $video
                    ->where('moderation_status', 'pending')
                    ->where('processing_status', 'ready')
                    ->whereNotNull('watermarked_at')
                    ->whereNotNull('resource_id'))
                ->orWhereHas('pendingPhysicalRevision'));

    }

    public static function query(): Builder
    {
        return static::filter(ModelProfile::query());
    }

    protected function getStats(): array
    {
        $count = static::query()->count();

        return [
            Stat::make('Contenido por revisar', $count)
                ->description($count > 0 ? 'Modelos con fotos, biografías, videos o datos por revisar' : 'No hay contenido pendiente de revisión')
                ->icon('heroicon-o-photo')
                ->color($count > 0 ? 'warning' : 'success')
                ->url(ModelProfileResource::getUrl('index', ['tab' => 'pending_content'])),
        ];
    }
}
