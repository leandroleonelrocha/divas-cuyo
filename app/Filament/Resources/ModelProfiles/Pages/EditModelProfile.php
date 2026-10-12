<?php

namespace App\Filament\Resources\ModelProfiles\Pages;

use App\Filament\Resources\ModelProfiles\ModelProfileResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditModelProfile extends EditRecord
{
    protected static string $resource = ModelProfileResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return Arr::only($data, [
            'name',
            'whatsapp',
            'location',
            'schedule_availability',
            'gender',
            'depilation',
            'rate_visibility',
            'rate_30min',
            'rate_30min_currency',
            'rate_60min',
            'rate_60min_currency',
        ]);
    }
}
