<?php

namespace App\Filament\Resources\ModelProfiles\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ModelProfileServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';

    protected static ?string $title = 'Servicios';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Servicios seleccionados')
            ->columns([
                TextColumn::make('name')
                    ->label('Servicio')
                    ->searchable(),
                TextColumn::make('service_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'in_person' ? 'Presencial' : 'Virtual')
                    ->color(fn (string $state): string => $state === 'in_person' ? 'warning' : 'info'),
                TextColumn::make('is_active')
                    ->label('Catálogo')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Activo' : 'Inactivo')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->modifyQueryUsing(fn ($query) => $query->orderBy('sort_order')->orderBy('name'))
            ->emptyStateHeading('No hay servicios seleccionados')
            ->emptyStateDescription('La modelo todavía no seleccionó servicios compatibles con su modalidad.');
    }
}
