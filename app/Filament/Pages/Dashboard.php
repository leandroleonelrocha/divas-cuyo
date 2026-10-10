<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ModelProfiles\ModelProfileResource;
use App\Filament\Widgets\ModelProfileStats;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Resumen';

    public function getWidgets(): array
    {
        return [ModelProfileStats::class];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('models')
                ->label('Ver modelos')
                ->icon('heroicon-o-users')
                ->url(ModelProfileResource::getUrl()),
        ];
    }
}
