<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ModelProfiles\ModelProfileResource;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function mount(): void
    {
        $this->redirect(ModelProfileResource::getUrl());
    }
}
