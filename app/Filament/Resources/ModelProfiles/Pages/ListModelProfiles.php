<?php

namespace App\Filament\Resources\ModelProfiles\Pages;

use App\Filament\Resources\ModelProfiles\ModelProfileResource;
use Filament\Resources\Pages\ListRecords;

class ListModelProfiles extends ListRecords
{
    protected static string $resource = ModelProfileResource::class;
}
