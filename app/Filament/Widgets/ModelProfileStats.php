<?php

namespace App\Filament\Widgets;

use App\Models\ModelProfile;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class ModelProfileStats extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected int|array|null $columns = 2;

    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return auth()->user()?->isVerifiedAdmin() ?? false;
    }

    protected function getStats(): array
    {
        $totals = ModelProfile::query()
            ->whereHas('user', fn (Builder $query) => $query->where('is_admin', false))
            ->selectRaw('SUM(CASE WHEN identity_status = ? THEN 1 ELSE 0 END) AS verified', ['approved'])
            ->selectRaw('SUM(CASE WHEN is_published = ? THEN 1 ELSE 0 END) AS published', [true])
            ->first();

        return [
            Stat::make('Modelos verificados', (int) $totals->verified)
                ->description('Identidad aprobada')
                ->icon('heroicon-o-check-badge')
                ->color('success'),
            Stat::make('Modelos publicados', (int) $totals->published)
                ->description('Publicación activada en el perfil')
                ->icon('heroicon-o-globe-alt')
                ->color('primary'),
        ];
    }
}
