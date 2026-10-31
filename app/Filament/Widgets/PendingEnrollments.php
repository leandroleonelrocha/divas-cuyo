<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ModelProfiles\ModelProfileResource;
use App\Models\ModelProfile;
use App\Services\AccountEnrollmentState;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class PendingEnrollments extends StatsOverviewWidget
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
        return $query->whereHas('user', fn (Builder $user) => $user
            ->where('is_admin', false)
            ->where('profile_completion_status', AccountEnrollmentState::PENDING));
    }

    public static function query(): Builder
    {
        return static::filter(ModelProfile::query());
    }

    protected function getStats(): array
    {
        $count = static::query()->count();

        return [
            Stat::make('Inscripciones por habilitar', $count)
                ->description($count > 0 ? 'Revisar inscripciones y habilitar acceso al panel' : 'No hay inscripciones pendientes')
                ->icon('heroicon-o-user-plus')
                ->color($count > 0 ? 'warning' : 'success')
                ->url(ModelProfileResource::getUrl('index', ['tab' => 'pending_enrollment'])),
        ];
    }
}
