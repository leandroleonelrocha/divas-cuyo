<?php

namespace App\Filament\Resources\ModelProfiles\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PublicationTypeHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'publicationTypeHistory';

    protected static ?string $title = 'Historial de tipo de publicación';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Transiciones de publicación')
            ->columns([
                TextColumn::make('changed_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('fromPublicationType.name')
                    ->label('Desde')
                    ->placeholder('Sin tipo anterior'),
                TextColumn::make('toPublicationType.name')
                    ->label('Hacia'),
                TextColumn::make('source')
                    ->label('Origen')
                    ->badge(),
                TextColumn::make('changedBy.email')
                    ->label('Realizado por')
                    ->placeholder('Sistema'),
                TextColumn::make('reason')
                    ->label('Motivo')
                    ->placeholder('—')
                    ->limit(80),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with(['fromPublicationType', 'toPublicationType', 'changedBy']))
            ->defaultSort('changed_at', 'desc')
            ->emptyStateHeading('No hay cambios registrados')
            ->emptyStateDescription('Las transiciones de tipo se conservarán para auditoría.');
    }
}
