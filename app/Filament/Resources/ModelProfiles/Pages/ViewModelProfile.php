<?php

namespace App\Filament\Resources\ModelProfiles\Pages;

use App\Filament\Resources\ModelProfiles\ModelProfileResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewModelProfile extends ViewRecord
{
    protected static string $resource = ModelProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar'),
            ...ModelProfileResource::moderationActions(),
        ];
    }
}
