<?php

namespace App\Filament\Resources\ModelProfiles\RelationManagers;

use App\Models\PublicationPayment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PublicationPaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Pagos de publicación';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('paid_at')->label('Fecha de pago')->date('d/m/Y')->sortable(),
                TextColumn::make('amount')->label('Importe')->money('ARS')->sortable(),
                TextColumn::make('period_days')->label('Período')->suffix(' días'),
                TextColumn::make('starts_at')->label('Inicio')->date('d/m/Y'),
                TextColumn::make('expires_at')->label('Vencimiento')->date('d/m/Y')->sortable(),
                TextColumn::make('registeredBy.email')->label('Registrado por'),
                TextColumn::make('internal_note')->label('Observación')->limit(50),
            ])
            ->recordUrl(null)
            ->emptyStateHeading('Sin pagos registrados')
            ->emptyStateDescription('Los pagos recibidos por WhatsApp aparecerán aquí después de registrarlos.');
    }
}
